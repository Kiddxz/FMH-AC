<?php

use App\Models\Backup;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Phase 18: php artisan fmh:security-check
| Checks the settings that must be changed before the clinic uses the system for real.
| It only reads; it changes nothing.
*/
Artisan::command('fmh:security-check', function () {
    $rows = [];
    $add = function (bool $ok, string $check, string $fix) use (&$rows) {
        $rows[] = [$ok ? 'OK' : 'FIX', $check, $ok ? '' : $fix];
    };

    $add(! config('app.debug'), 'APP_DEBUG is false (no technical error screens)', 'In .env set APP_DEBUG=false');
    $add(app()->environment('production'), 'APP_ENV is production', 'In .env set APP_ENV=production');
    $add(filled(config('app.key')), 'APP_KEY is set', 'Run: php artisan key:generate');
    $add(! str_starts_with((string) config('app.url'), 'https://') || (bool) config('session.secure'),
        'Session cookie is https-only when the site uses https', 'In .env set SESSION_SECURE_COOKIE=true');
    $add(! config('filesystems.disks.local.serve'), 'Private files (backups) cannot be opened by a web address', "In config/filesystems.php set 'serve' => false");

    // The demo accounts of the seeders must not keep their known passwords
    $demo = [
        'superadmin@fmhanimalclinic.com' => 'superadmin123',
        'admin@fmhanimalclinic.com' => 'admin123',
        'vet2@fmhanimalclinic.com' => 'vet12345',
        'assistant@fmhanimalclinic.com' => 'assistant123',
        'owner@fmhanimalclinic.com' => 'owner123',
    ];
    foreach ($demo as $email => $password) {
        $user = User::where('email', $email)->where('status', 'active')->first();
        $add(! ($user && Hash::check($password, $user->password)), "{$email} does not use its demo password",
            'Log in as this account and change the password in Profile (or deactivate the account)');
    }

    $latest = Backup::latest('created_at')->first();
    $add($latest && $latest->created_at->gt(now()->subDays(7)), 'A database backup was made in the last 7 days',
        'Super Admin → Backup & Recovery → Create Backup, then download it to a USB drive');

    $this->table(['Result', 'Check', 'How to fix'], $rows);
    $problems = collect($rows)->where(0, 'FIX')->count();
    $problems === 0
        ? $this->info('All checks passed.')
        : $this->warn("{$problems} item(s) to fix before the clinic uses the system. While you are still developing, APP_DEBUG/APP_ENV and the demo passwords may stay as they are.");
})->purpose('Check the security settings before the clinic uses the system');

/*
| Phase 18 (NFR-REQ004): php artisan fmh:compress-images
| Makes the big photos in public/image smaller so pages load faster (bg.jpg was 4.1 MB).
| The original photo is kept in storage/app/original-images, in case you need it again.
*/
Artisan::command('fmh:compress-images {--max-width=1920} {--quality=75}', function () {
    if (! function_exists('imagecreatefromstring')) {
        $this->error('The PHP "gd" extension is not turned on. In Laravel Herd: Settings → PHP → Extensions → turn on gd.');

        return 1;
    }

    $backupFolder = storage_path('app/original-images');
    File::ensureDirectoryExists($backupFolder);

    foreach (File::glob(public_path('image/*.jpg')) as $path) {
        $name = basename($path);
        $before = filesize($path);
        if ($before < 400 * 1024) {
            $this->line("{$name}: already small (" . round($before / 1024) . ' KB), skipped');
            continue;
        }

        if (! File::exists("{$backupFolder}/{$name}")) {
            File::copy($path, "{$backupFolder}/{$name}");
        }

        // imagecreatefromstring also reads photos that are really PNG files with a .jpg name (like bg.jpg)
        $image = imagecreatefromstring(File::get($path));
        $width = imagesx($image);
        if ($width > (int) $this->option('max-width')) {
            $image = imagescale($image, (int) $this->option('max-width'));
        }
        imageinterlace($image, true);   // the photo appears quickly, then gets sharper
        imagejpeg($image, $path, (int) $this->option('quality'));
        imagedestroy($image);

        clearstatcache(true, $path);
        $this->info(sprintf('%s: %s KB → %s KB', $name, round($before / 1024), round(filesize($path) / 1024)));
    }
})->purpose('Make the large photos in public/image smaller');

/*
| php artisan fmh:test-email yourname@gmail.com
| Sends one test email, to check the Gmail settings in .env before trying the real registration.
*/
Artisan::command('fmh:test-email {to : The email address that should receive the test}', function () {
    $to = $this->argument('to');
    $this->line('Mailer: ' . config('mail.default') . ' | Host: ' . config('mail.mailers.smtp.host') . ':' . config('mail.mailers.smtp.port')
        . ' | From: ' . config('mail.from.address'));

    if (config('mail.default') === 'log') {
        $this->warn('MAIL_MAILER=log: the email will only be written to storage/logs/laravel.log (not sent). Set MAIL_MAILER=smtp to send through Gmail.');
    }

    try {
        Mail::raw('This is a test email from the FMH Animal Clinic system. If you can read this, the Gmail settings are correct.', function ($message) use ($to) {
            $message->to($to)->subject('FMH Animal Clinic - Test email');
        });
    } catch (\Throwable $e) {
        $this->error('Sending failed: ' . $e->getMessage());
        $this->line('Check MAIL_USERNAME, MAIL_PASSWORD (the 16-letter Gmail App Password), MAIL_PORT and your internet, then run: php artisan config:clear');

        return 1;
    }

    config('mail.default') === 'log'
        ? $this->info('Test email written to storage/logs/laravel.log.')
        : $this->info("Test email sent to {$to}. Check the Inbox (and the Spam folder).");
})->purpose('Send a test email to check the Gmail (SMTP) settings');
