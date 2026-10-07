<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Backup;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Waiver;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The bell in the menu (like the notifications of Facebook). Each role sees the things it should act on:
 *  - Staff:       new online bookings to confirm, bills with a balance, inventory alerts
 *  - Vet/Admin:   signed waivers to review, today's appointments, inventory alerts
 *  - Super Admin: failed logins, new customer accounts, an old backup, inventory alerts (one line)
 * A notification goes away by itself once the thing is done (e.g. the booking is confirmed).
 * Everything stays inside the system: nothing is emailed or texted (paper Limitations).
 *
 * Each notification: ['icon' => emoji, 'tone' => 'red'|'orange'|'blue'|'green', 'title', 'text', 'url', 'at' => Carbon|null]
 */
class Notifications
{
    public static function for(User $user, string $area): Collection
    {
        // Computed once per page
        $attributes = request()->attributes;
        if ($attributes->has('notifications')) {
            return $attributes->get('notifications');
        }

        $notes = match ($area) {
            'staff' => self::staff($user),
            'admin' => self::admin($user),
            'superadmin' => self::superAdmin(),
            default => collect(),
        };
        $attributes->set('notifications', $notes);

        return $notes;
    }

    // ---------- Staff (receptionist / cashier) ----------
    private static function staff(User $user): Collection
    {
        $notes = collect();

        if ($user->can('appointments.manage')) {
            $notes = $notes->merge(self::newBookings('staff'));
        }

        if ($user->can('pos.manage')) {
            $open = Transaction::whereIn('status', ['unpaid', 'partial']);
            $count = (clone $open)->count();
            if ($count > 0) {
                $notes->push(self::note('💵', 'orange',
                    $count . ' ' . Str::plural('bill', $count) . ' still ' . ($count === 1 ? 'has' : 'have') . ' a balance',
                    'Total balance ₱' . number_format((float) (clone $open)->sum('balance'), 2) . '. Collect it when the owner pays.',
                    route('staff.transactions.index'),
                    (clone $open)->latest()->value('created_at')));
            }
        }

        return $notes->merge(self::inventory('staff'));
    }

    // ---------- Vet / Admin ----------
    private static function admin(User $user): Collection
    {
        $notes = collect();

        if ($user->can('waivers.review')) {
            Waiver::with('pet')->where('status', 'signed')->latest('signed_at')->limit(10)->get()
                ->each(fn (Waiver $waiver) => $notes->push(self::note('📝', 'blue',
                    'Waiver to review: ' . $waiver->reference,
                    ($waiver->pet?->name ?? 'A pet') . '\'s owner signed it. Please review it.',
                    route('admin.waivers.show', $waiver), $waiver->signed_at)));
        }

        if ($user->can('appointments.view')) {
            $today = Appointment::whereDate('appointment_date', today())->where('status', 'confirmed');
            $mine = (clone $today)->where('veterinarian_id', $user->id)->count();
            $all = (clone $today)->count();
            if ($all > 0) {
                $notes->push(self::note('📅', 'blue',
                    $all . ' confirmed ' . Str::plural('appointment', $all) . ' today',
                    $mine > 0 ? $mine . ' of them ' . ($mine === 1 ? 'is' : 'are') . ' assigned to you.' : 'None is assigned to you yet.',
                    route('admin.appointments.index', ['date' => today()->toDateString()]), null));
            }
        }

        if ($user->can('appointments.manage')) {
            $notes = $notes->merge(self::newBookings('admin'));
        }

        return $notes->merge(self::inventory('admin'));
    }

    // ---------- Super Admin ----------
    private static function superAdmin(): Collection
    {
        $notes = collect();

        $failed = ActivityLog::whereIn('action', ['login_failed', 'login_blocked', 'login_throttled'])
            ->where('created_at', '>=', now()->subDay());
        $failedCount = (clone $failed)->count();
        if ($failedCount > 0) {
            $notes->push(self::note('🔐', $failedCount >= 5 ? 'red' : 'orange',
                $failedCount . ' failed ' . Str::plural('login', $failedCount) . ' in the last 24 hours',
                'Check the Activity Logs if you do not recognize them.',
                route('superadmin.activity-logs', ['action' => 'login_failed']),
                (clone $failed)->latest()->value('created_at')));
        }

        $newCustomers = User::whereHas('role', fn ($q) => $q->where('slug', Role::CUSTOMER))
            ->whereNotNull('email_verified_at')->where('created_at', '>=', now()->subDays(7));
        $customerCount = (clone $newCustomers)->count();
        if ($customerCount > 0) {
            $notes->push(self::note('👥', 'blue',
                $customerCount . ' new customer ' . Str::plural('account', $customerCount) . ' this week',
                'Pet owners who registered and verified their email.',
                route('superadmin.users.index', ['role' => Role::CUSTOMER]),
                (clone $newCustomers)->latest()->value('created_at')));
        }

        $lastBackup = Backup::latest()->value('created_at');
        if (! $lastBackup || Carbon::parse($lastBackup)->lt(now()->subDays(7))) {
            $notes->push(self::note('💾', 'red',
                $lastBackup ? 'No backup in the last 7 days' : 'No backup yet',
                $lastBackup ? 'The last backup was ' . Carbon::parse($lastBackup)->diffForHumans() . '. Make a new one.' : 'Make the first backup of the clinic data.',
                route('superadmin.backups'), null));
        }

        $alerts = InventoryAlerts::summary();
        if ($alerts['count'] > 0) {
            $notes->push(self::note('📦', 'orange',
                $alerts['count'] . ' inventory ' . Str::plural('alert', $alerts['count']),
                $alerts['low']->count() . ' low / out of stock, ' . $alerts['expiring']->count() . ' expiring soon, ' . $alerts['expired']->count() . ' expired.',
                route('superadmin.inventory'), null));
        }

        return $notes;
    }

    // ---------- shared ----------

    // Online bookings that are still Pending (the clinic has not confirmed them yet)
    private static function newBookings(string $area): Collection
    {
        return Appointment::with('pet')->where('status', 'pending')->whereDate('appointment_date', '>=', today())
            ->latest()->limit(10)->get()
            ->map(fn (Appointment $a) => self::note('📅', 'blue',
                'New booking to confirm: ' . ($a->pet?->name ?? 'a pet'),
                $a->service_names . ' · ' . $a->appointment_date->format('M j') . ', ' . Carbon::parse($a->appointment_time)->format('g:i A') . ' · ' . $a->reference,
                route($area . '.appointments.show', $a), $a->created_at));
    }

    // Each inventory alert as its own notification, the most urgent first
    private static function inventory(string $area): Collection
    {
        $alerts = InventoryAlerts::summary();
        $notes = collect();

        foreach ($alerts['expired'] as $batch) {
            $notes->push(self::note('✖', 'red', $batch->item->name . ' has expired stock',
                $batch->quantity . ' ' . $batch->item->unit . ' expired on ' . $batch->expiration_date->format('M j, Y') . '. Please dispose of it.',
                route($area . '.inventory.show', $batch->item), null));
        }
        foreach ($alerts['low'] as $item) {
            $left = (int) $item->usable_stock;
            $notes->push(self::note('⚠️', $left <= 0 ? 'red' : 'orange', $item->name . ($left <= 0 ? ' is out of stock' : ' is running low'),
                $left . ' ' . $item->unit . ' left (minimum ' . $item->reorder_level . '). Time to restock.',
                route($area . '.inventory.show', $item), null));
        }
        foreach ($alerts['expiring'] as $batch) {
            $days = (int) today()->diffInDays($batch->expiration_date);
            $notes->push(self::note('⏰', 'orange', $batch->item->name . ' expires ' . ($days === 0 ? 'today' : 'in ' . $days . ' ' . Str::plural('day', $days)),
                $batch->quantity . ' ' . $batch->item->unit . ', expiry ' . $batch->expiration_date->format('M j, Y') . '. Use it first.',
                route($area . '.inventory.show', $batch->item), null));
        }

        return $notes;
    }

    private static function note(string $icon, string $tone, string $title, string $text, string $url, $at): array
    {
        return ['icon' => $icon, 'tone' => $tone, 'title' => $title, 'text' => $text, 'url' => $url, 'at' => $at ? Carbon::parse($at) : null];
    }
}
