<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Checks that no project file still contains the PowerShell lines that were used to write files
 * in the early phases ($code = @' ... '@ / [IO.File]::WriteAllText / Write-Host "OK: ...").
 * If one is pasted into a file by mistake, it shows up as text on the page.
 */
class LeftoverCodeTest extends TestCase
{
    public function test_no_file_contains_powershell_write_lines(): void
    {
        $found = [];
        foreach (['app', 'bootstrap', 'config', 'database', 'resources', 'routes', 'public/css', 'public/js', 'tests'] as $folder) {
            foreach (File::allFiles(base_path($folder)) as $file) {
                if ($file->getFilename() === 'LeftoverCodeTest.php') {
                    continue;
                }
                foreach (file($file->getPathname()) as $number => $line) {
                    if (preg_match('/\[IO\.File\]::WriteAllText|^\s*\$code\s*=\s*@\'|^\s*\'@\s*$|Write-Host\s+"OK:/', $line)) {
                        $found[] = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getPathname()) . ' (line ' . ($number + 1) . ')';
                    }
                }
            }
        }

        $this->assertSame([], $found, "Remove the PowerShell lines from these files:\n" . implode("\n", $found));
    }
}
