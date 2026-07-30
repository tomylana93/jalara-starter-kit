<?php

namespace App\Providers;

use App\Enums\PasswordPolicy;
use App\Settings\AuthenticationSettings;
use App\Settings\SettingsResolver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use LogicException;

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
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        throw_if(
            app()->isProduction() && ! in_array(DB::getDefaultConnection(), ['mysql', 'mariadb', 'pgsql'], true),
            LogicException::class,
            __('system.exception.production_database'),
        );

        Password::defaults(fn (): Password => SettingsResolver::tryResolve(AuthenticationSettings::class)
            ?->passwordPolicy
            ->rule()
            ?? PasswordPolicy::Strict->rule(),
        );
    }
}
