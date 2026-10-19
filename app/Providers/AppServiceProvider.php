<?php

namespace App\Providers;

use App\Authorization\RolePermissions;
use App\Contracts\SmsGateway;
use App\Models\User;
use App\Services\LogSmsGateway;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SmsGateway::class, LogSmsGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach (RolePermissions::names() as $permission) {
            Gate::define($permission, fn (User $user): bool => $user->permits($permission));
        }

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
