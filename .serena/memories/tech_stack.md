# Tech Stack

- Runtime environment: Linux; PHP 8.5.8 CLI; Laravel 13.22.0. Composer manifest requires PHP `^8.5`.
- Backend: Laravel 13, Inertia Laravel 3, Fortify 1, Wayfinder 0.x, Chisel 0.x.
- Backend quality/test: Pest 4 + PHPUnit 12 ecosystem, Larastan 3, Pint 1, Laravel Boost 2, Pail 1, Sail 1.
- Frontend: Vue 3.5, Inertia Vue 3, TypeScript 5.x strict mode, Vite 8, Tailwind CSS 4, Wayfinder Vite 0.x, Reka UI 2, VueUse 12, Lucide Vue.
- Frontend quality: ESLint 9 flat config, Prettier 3 with Tailwind plugin, vue-tsc 2.
- Package managers: Composer 2.9; pnpm 11. The scripts also invoke npm-compatible package scripts, but dependency installation is standardized on pnpm.
- Build inputs: `resources/css/app.css` and `resources/js/app.ts`; Vite plugins include Laravel, Inertia, Vue, Tailwind, and Wayfinder.