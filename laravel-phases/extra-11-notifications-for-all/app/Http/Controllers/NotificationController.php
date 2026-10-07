<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Services\Notifications;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * GET /notifications
 * The list inside the bell. It is loaded only when the bell is clicked, so the pages stay fast.
 * The role of the logged-in user decides what is shown (customers have no bell).
 */
class NotificationController extends Controller
{
    private const AREAS = [
        Role::STAFF => 'staff',
        Role::VET_ADMIN => 'admin',
        Role::SUPER_ADMIN => 'superadmin',
    ];

    public function __invoke(Request $request): View
    {
        $area = self::AREAS[$request->user()->role?->slug] ?? null;
        abort_unless($area, 403);

        return view('partials.notification-list', [
            'notes' => Notifications::for($request->user(), $area),
            'limit' => 10,
        ]);
    }

    // The area of a user, for the number on the bell
    public static function areaOf(?\App\Models\User $user): ?string
    {
        return self::AREAS[$user?->role?->slug] ?? null;
    }
}
