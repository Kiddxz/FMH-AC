<?php

namespace App\Console\Commands;

use App\Rules\RealEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * php artisan fmh:update-email-blocklist
 *
 * Downloads the public lists of temporary / throw-away email domains (Temp Mail, 10 Minute Mail, ...)
 * and saves them in storage/app/disposable_email_domains.txt. Registration then blocks every domain
 * in that file. Run it once now, and again from time to time, because new temporary domains appear often.
 *
 * Sources (free to use):
 *  - github.com/disposable-email-domains/disposable-email-domains  (CC0 public domain)
 *  - github.com/FGRibreau/mailchecker                              (MIT license)
 */
class UpdateEmailBlocklist extends Command
{
    protected $signature = 'fmh:update-email-blocklist';

    protected $description = 'Download the latest list of temporary (fake) email domains used to block registrations';

    private const SOURCES = [
        'https://raw.githubusercontent.com/disposable-email-domains/disposable-email-domains/main/disposable_email_blocklist.conf',
        'https://raw.githubusercontent.com/FGRibreau/mailchecker/master/list.txt',
    ];

    public function handle(): int
    {
        $domains = [];

        foreach (self::SOURCES as $url) {
            $this->line('Downloading ' . $url);
            try {
                $response = Http::timeout(60)->get($url);
            } catch (\Throwable $e) {
                $this->warn('  Could not download it: ' . $e->getMessage());
                continue;
            }
            if ($response->failed()) {
                $this->warn('  Could not download it (HTTP ' . $response->status() . ').');
                continue;
            }

            $count = 0;
            foreach (preg_split('/\R/', $response->body()) as $line) {
                $domain = strtolower(trim($line));
                if ($domain !== '' && ! str_starts_with($domain, '#') && preg_match('/^[a-z0-9.-]+\.[a-z0-9-]+$/', $domain)) {
                    $domains[$domain] = true;
                    $count++;
                }
            }
            $this->info('  ' . number_format($count) . ' domains');
        }

        if ($domains === []) {
            $this->error('Nothing was downloaded. Check the internet connection and try again. The old list (if any) was kept.');

            return self::FAILURE;
        }

        // Real email providers are never blocked, even if a list has them by mistake
        foreach (RealEmail::ALWAYS_ALLOWED as $domain) {
            unset($domains[$domain]);
        }

        $list = array_keys($domains);
        sort($list);
        file_put_contents(RealEmail::blocklistPath(), implode("\n", $list) . "\n");

        $this->info('Saved ' . number_format(count($list)) . ' temporary email domains to ' . RealEmail::blocklistPath());

        return self::SUCCESS;
    }
}
