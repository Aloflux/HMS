<?php

namespace Tests;

use App\Support\FacilityContext;
use App\Support\MailPressure;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        FacilityContext::forget();
        MailPressure::forget();

        parent::tearDown();
    }
}
