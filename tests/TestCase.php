<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $sysTemp = sys_get_temp_dir();
            putenv("TMP={$sysTemp}");
            putenv("TEMP={$sysTemp}");
        }

        parent::setUp();
    }
}
