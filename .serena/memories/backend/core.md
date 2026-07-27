# Backend

- Laravel application code is under `app/`; HTTP controllers and Form Requests are grouped by feature (for example `app/Http/Controllers/Settings`, `app/Http/Requests/Settings`).
- `routes/web.php` owns public/dashboard routes and includes `routes/settings.php`; named routes are the invariant for URL generation.
- Inertia pages are returned with `Inertia::render('Path/Component', props)`; mutations conventionally redirect with `to_route()` and may publish toast data via `Inertia::flash('toast', ...)`.
- Fortify owns authentication backend behavior; custom actions live in `app/Actions/Fortify`, reusable auth validation rules in `app/Concerns`, and configuration/bootstrap in `app/Providers/FortifyServiceProvider.php`.
- Authorization/validation belongs in policies or Form Requests, not ad-hoc controller logic.
- Models live in `app/Models`; use factories from `database/factories` in tests.
- Feature tests live in `tests/Feature`; feature tests automatically extend `Tests\TestCase` and use `RefreshDatabase` via `tests/Pest.php`. Unit tests live in `tests/Unit`.
- Create framework artifacts with `php artisan make:*` and `--no-interaction`; inspect schema before migrations/models and prefer Eloquent/resources for APIs.