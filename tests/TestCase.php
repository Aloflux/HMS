<?php

namespace Tests;

use App\Http\Middleware\SetFacilityContext;
use App\Models\User;
use App\Support\FacilityContext;
use App\Support\MailPressure;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Sign in as the user and, when they work at exactly one facility, open it as signing in with its code would.
     * Someone with several facilities still has to pick one with its password.
     */
    public function actingAs(UserContract $user, $guard = null)
    {
        parent::actingAs($user, $guard);

        if ($user instanceof User && ! $user->is_platform_admin) {
            $usable = $user->usableMemberships();

            if ($usable->count() === 1) {
                $this->withSession([SetFacilityContext::SESSION_KEY => $usable->first()->id]);
            }
        }

        return $this;
    }

    protected function tearDown(): void
    {
        FacilityContext::forget();
        MailPressure::forget();

        parent::tearDown();
    }
}
