<?php

namespace App\Providers;

use App\Enums\PasswordPolicy;
use App\Settings\AuthenticationSettings;
use App\Settings\SettingsResolver;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\DevCommands;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
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
        $this->registerDevCommands();
    }

    /**
     * Add a worker for the long-running queue connection to `composer run dev`.
     *
     * `DevCommands::registerDefaults()` starts `queue:listen` with no connection
     * argument, which serves the default `database` connection only. Backups run
     * on `database-long` because `retry_after` is per-connection, so without this
     * their jobs sit in the queue unclaimed and the run stays pending forever -
     * with no failure anywhere to explain why.
     *
     * `--timeout=0` because the point of the connection is work that outlives the
     * default limits.
     */
    private function registerDevCommands(): void
    {
        if (class_exists(DevCommands::class)) {
            DevCommands::artisan(
                'queue:listen database-long --tries=1 --timeout=0',
                'queue-long',
            );
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();
        $this->configureVite();
    }

    /**
     * Point Vite at the isolated bundle built for browser tests.
     *
     * The Playwright bundle must never share a build directory or hot file with
     * a development session, because both processes would then create and
     * delete the same coordination files.
     */
    protected function configureVite(): void
    {
        if (! config('app.vite.isolated_assets')) {
            return;
        }

        Vite::useBuildDirectory((string) config('app.vite.isolated_build_directory'))
            ->useHotFile(public_path((string) config('app.vite.isolated_hot_file')));
    }

    /**
     * Configure the application's named rate limiters.
     */
    protected function configureRateLimiting(): void
    {
        /*
         * Keyed by sender rather than by IP, so one account cannot flood a
         * conversation from several tabs or devices at once.
         */
        RateLimiter::for('chat-messages', fn (Request $request): Limit => Limit::perMinute(30)
            ->by($request->user()?->getAuthIdentifier() ?? $request->ip()));
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
            $this->isConfiguredProduction() && ! in_array(DB::getDefaultConnection(), ['mysql', 'mariadb', 'pgsql'], true),
            LogicException::class,
            __('system.exception.production_database'),
        );

        Password::defaults(fn (): Password => SettingsResolver::tryResolve(AuthenticationSettings::class)
            ?->passwordPolicy
            ->rule()
            ?? PasswordPolicy::Strict->rule(),
        );
    }

    /**
     * Determine whether this is a production application that has been configured.
     *
     * Composer boots the application through `package:discover` while
     * installing dependencies, which happens before the Laravel installer
     * copies `.env` into place. That boot has no application key and only
     * falls back to the production environment because `APP_ENV` is unset, so
     * it must not be mistaken for a real production deployment. A configured
     * production application always carries an application key, whether it
     * comes from `.env` or from the server environment.
     */
    protected function isConfiguredProduction(): bool
    {
        return app()->isProduction() && (string) config('app.key') !== '';
    }
}
