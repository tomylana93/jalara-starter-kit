# Tech Stack

- PHP ^8.3 (host project guidance: PHP 8.5); Laravel 13.31; Inertia Laravel 3.3; Fortify 1.39; Wayfinder 0.1.
- Vue 3.5 + TypeScript + Vite 8/vite-plus; Tailwind CSS 4 via @tailwindcss/vite; shadcn-vue new-york-v4/Reka UI/Lucide.
- pnpm lockfile/workspace; Composer lockfile.
- Pest 5 + Laravel plugin; RefreshDatabase is global for Feature tests. PHPStan level 7 (app, bootstrap/app.php, config, database, routes); Pint Laravel preset.
- Vite registers Inertia, Vue, Tailwind, and Wayfinder with form variants; generated frontend route/controller helpers live under resources/js routes/actions and are lint/format ignored.