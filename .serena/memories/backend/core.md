# Backend

- Laravel application code is under `app/`; HTTP controllers and Form Requests are grouped by feature (for example `app/Http/Controllers/Account`, `app/Http/Requests/Account`).
- `routes/web.php` owns public/dashboard routes and includes `routes/account.php`; named routes are the invariant for URL generation. The `Settings` domain (namespace/routes/pages) was fully retired in favor of `Account` (prefix `account`, name `account.*`) — the `Settings` namespace is reserved for future application-level settings, not user account management.
- `app/Actions/` holds only Actions. Exceptions live in `app/Exceptions/{Domain}/`, strategies and stateful services in `app/Support/{Domain}/`, readonly data objects in `app/Data/{Domain}/`. Sole exception: `Actions/Fortify/ResetUserPassword`, whose entry point is fixed by the Fortify contract.
- `app/Data/{Domain}/` is the action-boundary data layer: `*Data` in, `*Result` out, both `final readonly`. Form Requests build the inbound object in `toData()`; console commands build it directly, which is why nothing in `app/Data` may depend on `Illuminate\Http`. Enums resolve in `toData()`, so an Action assigns rather than parses. Layer constraints are a Project Rule on `app/Data/**`.
- Business mutations (profile update, password update, account deletion) live in concrete `App\Actions\{Domain}\*` classes with a single `handle()` method, no interface/base class. Controllers inject the Action via method injection and stay thin: validation/authorization stays in Form Requests, HTTP concerns (redirect, flash, session lifecycle) stay in the controller, only the mutation itself goes in the Action.
- Inertia pages are returned with `Inertia::render('Path/Component', props)`; mutations conventionally redirect with `to_route()` and may publish toast data via `Inertia::flash('toast', ...)`.
- Fortify owns authentication backend behavior; custom actions live in `app/Actions/Fortify`, reusable auth validation rules in `app/Concerns`, and configuration/bootstrap in `app/Providers/FortifyServiceProvider.php`.
- Failed logins are throttled by the configured security limits per normalized email + IP; they never mutate or suspend the user account. `UserStatus::Suspended` remains an explicit account state, with optional expiry handling.
- `User::reactivateExpiredSuspension()` is the single owner of automatic suspension expiry. It persists `Suspended` → `Active` and clears `suspended_until` only for a non-null past expiry. `AuthenticateUser` may call it only after valid credentials, while `EnforceUserAccess` calls it before blocking an existing session; manual and future-dated suspensions remain blocked. Any code setting a status must therefore null `suspended_until`, or a stale expiry silently lifts the new suspension.
- Reusable search/sort/pagination contract for list screens: `mem:backend/tables`.
- Notification payload contract, UUIDv4 notification ids and their ordering
  tie-breaker, broadcast channel authorization pitfalls, and the shared bell prop
  name: `mem:backend/notifications`.
- Authorization/validation belongs in policies or Form Requests, not ad-hoc controller logic.
- Reference/bootstrap data command ownership, dry-run, and secret-handling invariants: `mem:backend/data_initialization`.
- Typed application settings (persistence, runtime application, maintenance/verification middleware, settings endpoints): `mem:backend/settings`.
- Direct-message schema, the notification-context rule, the chat feature toggle, the audit surface, and the Larastan paginator constraint: `mem:backend/chat`.
- Queued image-upload lifecycle (202 intake contract, active-target locking, adaptive format/downscale rules, republication authorization, and the orphan sweep): `mem:backend/media_uploads`.
- Models live in `app/Models`; use factories from `database/factories` in tests.
- `User::primaryRole()` is the sole deterministic resolver for all displayed primary roles, including chat, master-data table, edit, and export presentation; it returns the first loaded role in `Role::cases()` privilege order (Super Admin, Admin, User). Callers must preload `roles`, and HTTP presentation-only role loading occurs only after authorization.
- Feature tests live in `tests/Feature`; feature tests automatically extend `Tests\TestCase` and use `RefreshDatabase` via `tests/Pest.php`. Unit tests live in `tests/Unit`.
- Create framework artifacts with `php artisan make:*` and `--no-interaction`; inspect schema before migrations/models and prefer Eloquent/resources for APIs.