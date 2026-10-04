<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Phase 17: files written during a test (e.g. backups) go to a temporary folder
        // that is thrown away afterwards, so tests never fill up storage/app/private.
        Storage::fake('local');
    }
}
