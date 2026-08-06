# Tech Stack

- Runtime: Linux, PHP 8.5, Laravel 13; Composer requires PHP `^8.5`.
- Backend: Inertia Laravel 3, Fortify 1, Wayfinder 0.x, Chisel 0.x, Spatie Permission 7, Spatie Settings 3.
- Backend quality/test: Pest 5 + PHPUnit 13 for Unit/Feature only, Larastan 3, Rector 2, Pint 1, Laravel Boost 2.
- Frontend: Vue 3.5, Inertia Vue 3, TypeScript 5 strict, Vite 8, Tailwind CSS 4, Reka UI 2, VueUse 12.
- Frontend test: Vitest 4 + Vue Test Utils 2 + jsdom for unit/component tests; Playwright Test 1.62 with Chromium for E2E.
- Package managers: Composer 2 and pnpm 11.
- Documents/spreadsheets: Spatie Simple Excel 3 reads and writes `.xlsx`; Spatie Laravel PDF 2 renders Blade through Spatie Browsershot 5, which drives a real Chrome from Node via the `puppeteer` runtime dependency. Puppeteer's browser download is declined in `pnpm-workspace.yaml`, so a Chrome path must come from the environment. `ext-intl` is required for locale month names.
- Build inputs: `resources/css/app.css`, `resources/css/pdf.css` (print-only, inlined into PDF templates), and `resources/js/app.ts`; Vite owns shared aliases/plugins and Vitest configuration.