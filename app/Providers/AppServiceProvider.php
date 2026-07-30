<?php

namespace App\Providers;

use App\Events\ApplicationSubmitted;
use App\Listeners\LogApplicationSubmitted;
use App\Models\Application;
use App\Models\License;
use App\Models\User;
use App\Policies\ApplicationPolicy;
use App\Policies\LicensePolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Gate::policy(Application::class, ApplicationPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(License::class, LicensePolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

        Gate::before(function (User $user, string $ability) {
            if ($user->hasRole('super-admin')) {
                return true;
            }

            return null;
        });

        Event::listen(ApplicationSubmitted::class, LogApplicationSubmitted::class);

        // ── Audit: giriş / çıkış ──────────────────────────────────────────
        Event::listen(Login::class, function (Login $event): void {
            AuditLogger::log(
                'auth.login',
                sprintf('%s sisteme giriş yaptı.', $event->user->name ?? 'Bilinmiyor'),
                'User',
                $event->user->id,
                ['email' => $event->user->email, 'guard' => $event->guard],
            );
        });

        Event::listen(Logout::class, function (Logout $event): void {
            AuditLogger::log(
                'auth.logout',
                sprintf('%s sistemden çıkış yaptı.', $event->user?->name ?? 'Bilinmiyor'),
                'User',
                $event->user?->id,
            );
        });
    }
}
