# Core

- Laravel + Inertia Vue starter kit with verified Dashboard and account settings (profile, appearance, password, 2FA, passkeys). No public Welcome page: route `/` (named `home`) redirects guests to login and authenticated users to dashboard.
- Backend source is app/, config/, database/, routes/; frontend is resources/js/ and resources/css/; Blade is only the Inertia root view. Tests are Pest feature/unit tests under tests/.
- Project rules are intended under .ai/rules; inspect the index and applicable rules before edits. At onboarding time the directory contained no rule files.
- For package versions and toolchain details, read `mem:tech_stack`. For local workflows, read `mem:suggested_commands`. For code patterns, read `mem:conventions`. For verification gates, read `mem:task_completion`.
- For request, auth, routing, and settings behavior read `mem:backend/core`; for Vue pages, layouts, components, and generated helpers read `mem:frontend/core`.
