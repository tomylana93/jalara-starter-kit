<?php

namespace App\Providers;

use App\Enums\PasswordPolicy;
use App\Settings\AuthenticationSettings;
use App\Settings\SettingsResolver;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\DevCommands;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\ExceptionResponse;
use Inertia\Inertia;
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
        $this->configureErrorPages();
    }

    /**
     * Render error responses as full Inertia pages rather than bare responses.
     *
     * A plain response to an Inertia visit is not recognised as a page, so the
     * client falls back to its modal error overlay - the whole application
     * stays behind a dialog instead of being replaced by the error screen.
     *
     * `withSharedData()` resolves the Inertia middleware explicitly, because an
     * exception may be thrown before or outside it; without it these pages lose
     * branding, locale, and the permission flags they render from. It is the
     * reason this is registered here rather than as a plain `respond()` callback
     * in `bootstrap/app.php`, where that method does not exist.
     */
    protected function configureErrorPages(): void
    {
        Inertia::handleExceptionsUsing(function (ExceptionResponse $response): ?ExceptionResponse {
            /* API clients keep their JSON body; an Inertia visit sends `Accept: text/html`. */
            if ($response->request->is('api/*') || $response->request->expectsJson()) {
                return null;
            }

            /* Maintenance is a product state, so its screen renders in every environment. */
            if ($response->statusCode() === 503) {
                return $response->render('Maintenance')->withSharedData();
            }

            /* A real failure is more useful as the local debug modal. */
            if (app()->environment('local')) {
                return null;
            }

            return in_array($response->statusCode(), [403, 404, 500], true)
                ? $response->render('ErrorPage', ['status' => $response->statusCode()])->withSharedData()
                : null;
        });
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

        /*
         * Strict models outside production. `preventAccessingMissingAttributes`
         * is the demanding half: a model hydrated without a column throws on
         * access rather than reporting null, so `#[Appends]` accessors reading
         * a column a factory never set fail loudly instead of at serialization
         * time in a consumer's browser. `FactoryCoverageTest` holds the other
         * end of that contract.
         */
        Model::shouldBeStrict(! app()->isProduction());

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
