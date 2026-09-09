# Backend Core

- Entry points: routes/web.php exposes `/` (named `home`) as an auth-aware redirect (authenticated to dashboard, guests to login) plus the verified dashboard; routes/settings.php owns authenticated profile/security/appearance routes.
- Inertia middleware shares app name, authenticated user, and sidebar state derived from the `sidebar_state` cookie.
- FortifyServiceProvider owns custom create/reset actions, every auth Inertia view, and rate limiters: login and 2FA 5/minute; passkeys 10/minute.
- Settings use ProfileController and SecurityController plus Form Requests. Security page exposes feature-gated passkeys and 2FA props; password update and profile update flash a toast.
- Read `mem:conventions` for backend-to-frontend route/form rules and `mem:task_completion` for verification.
