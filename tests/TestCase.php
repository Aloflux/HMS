<?php

namespace Tests;

use App\Support\FacilityContext;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        FacilityContext::forget();

        parent::tearDown();
    }
}
