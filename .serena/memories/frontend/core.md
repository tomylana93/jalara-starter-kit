# Frontend Core

- Bootstrap: resources/js/app.ts initializes Inertia Vue, dynamically resolves pages from resources/js/pages, wires progress, and installs flash toast behavior.
- Pages: public/primary pages are in resources/js/pages; auth pages in pages/auth; settings pages in pages/settings. Layouts are in resources/js/layouts (auth, app, settings).
- Reusable application components are resources/js/components; shadcn-vue primitives are resources/js/components/ui and should be treated as generated/vendor-like (Vite excludes them from lint/format).
- Styling is Tailwind 4 from resources/css/app.css; use shared `cn`/CVA utilities where components already do.
- Generated Wayfinder helpers bridge frontend and Laravel endpoints; inspect imports before adding navigation or forms. Read `mem:conventions` for the required helper/form patterns and `mem:task_completion` for checks.