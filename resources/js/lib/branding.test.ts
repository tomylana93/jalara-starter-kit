import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import path from 'node:path';
import { test } from 'vitest';
import {
    appLayoutPresets,
    applicationTitle,
    authLayoutPresets,
    colorThemePresets,
    defaultBranding,
    fontPairPresets,
    resolvePreset,
    syncBrandingAttributes,
} from './branding.ts';

const projectRoot = path.resolve(import.meta.dirname, '../../..');

const readSource = (relativePath: string): Promise<string> =>
    readFile(path.join(projectRoot, relativePath), 'utf8');

const brandTokens = [
    '--primary:',
    '--primary-foreground:',
    '--ring:',
    '--chart-1:',
    '--chart-2:',
    '--chart-3:',
    '--chart-4:',
    '--chart-5:',
    '--sidebar-primary:',
    '--sidebar-primary-foreground:',
    '--sidebar-ring:',
];

const neutralSurfaceTokens = [
    '--background:',
    '--card:',
    '--popover:',
    '--secondary:',
    '--muted:',
    '--accent:',
    '--border:',
    '--input:',
    '--sidebar-background:',
    '--sidebar-accent:',
    '--sidebar-border:',
];

const rampTokens = [
    '--theme-50:',
    '--theme-100:',
    '--theme-200:',
    '--theme-300:',
    '--theme-400:',
    '--theme-500:',
    '--theme-600:',
    '--theme-700:',
    '--theme-800:',
    '--theme-900:',
    '--theme-950:',
];

const blockFor = (css: string, selector: string): string => {
    const start = css.indexOf(`${selector} {`);

    assert.notEqual(start, -1, `missing block for ${selector}`);

    return css.slice(start, css.indexOf('}', start));
};

test('resolves every layout preset and falls back to the default', () => {
    const authMap = { simple: 'Simple', card: 'Card', split: 'Split' };
    const appMap = { sidebar: 'Sidebar', header: 'Header' };

    for (const preset of authLayoutPresets) {
        assert.equal(
            resolvePreset(authMap, preset, defaultBranding.authLayout),
            authMap[preset],
        );
    }

    for (const preset of appLayoutPresets) {
        assert.equal(
            resolvePreset(appMap, preset, defaultBranding.appLayout),
            appMap[preset],
        );
    }

    assert.equal(resolvePreset(authMap, null, 'simple'), 'Simple');
    assert.equal(resolvePreset(authMap, undefined, 'simple'), 'Simple');
    assert.equal(
        resolvePreset(
            authMap,
            'unknown' as unknown as keyof typeof authMap,
            'simple',
        ),
        'Simple',
    );
    assert.equal(
        resolvePreset(
            appMap,
            'unknown' as unknown as keyof typeof appMap,
            'sidebar',
        ),
        'Sidebar',
    );
});

test('maps every layout preset to its own component', async () => {
    const source = await readSource('resources/js/composables/useBranding.ts');

    const expected: Record<string, string> = {
        simple: 'AuthSimpleLayout',
        card: 'AuthCardLayout',
        split: 'AuthSplitLayout',
        sidebar: 'AppSidebarLayout',
        header: 'AppHeaderLayout',
    };

    for (const [preset, component] of Object.entries(expected)) {
        assert.match(source, new RegExp(`${preset}:\\s*${component},`));
        assert.match(
            source,
            new RegExp(`import ${component} from '@/layouts/`),
        );
    }
});

test('builds the document title from the application identity', () => {
    assert.equal(
        applicationTitle('Dashboard', 'Jalara App'),
        'Dashboard - Jalara App',
    );
    assert.equal(applicationTitle('', 'Jalara App'), 'Jalara App');
    assert.equal(applicationTitle(null, 'Jalara App'), 'Jalara App');
    assert.equal(applicationTitle(undefined, 'Jalara App'), 'Jalara App');
    assert.equal(applicationTitle('Dashboard', '  '), 'Dashboard - Laravel');
});

test('drives the visible identity from the application name', async () => {
    /*
     * BrandIdentity is the single owner of the visible identity: it resolves the
     * identity mode, the dark variants, and the static fallback. Every branded
     * surface must render through it rather than reaching for the name itself.
     */
    const identity = await readSource(
        'resources/js/components/BrandIdentity.vue',
    );

    assert.match(identity, /page\.props\.name/);
    assert.doesNotMatch(identity, /companyName/);

    for (const file of [
        'resources/js/components/AppLogo.vue',
        'resources/js/layouts/auth/AuthSplitLayout.vue',
    ]) {
        const source = await readSource(file);

        assert.match(source, /BrandIdentity/);
        assert.doesNotMatch(source, /companyName/);
    }

    const entry = await readSource('resources/js/app.ts');

    assert.match(entry, /applicationTitle\(/);
    assert.match(entry, /page\.props\.name/);
    assert.doesNotMatch(entry, /companyName/);
});

test('renders the branding footer text in every layout', async () => {
    const footer = await readSource('resources/js/components/AppFooter.vue');

    assert.match(footer, /branding\.footerText/);
    // The footer is pinned to the bottom of its column, not trailing content.
    assert.match(footer, /class="mt-auto/);

    for (const layout of [
        'resources/js/layouts/auth/AuthSimpleLayout.vue',
        'resources/js/layouts/auth/AuthCardLayout.vue',
        'resources/js/layouts/auth/AuthSplitLayout.vue',
        'resources/js/layouts/app/AppSidebarLayout.vue',
        'resources/js/layouts/app/AppHeaderLayout.vue',
    ]) {
        assert.match(await readSource(layout), /<AppFooter \/>/);
    }
});

test('syncs the branding attributes onto the document element', () => {
    const documentElement = { dataset: {} as Record<string, string> };

    Object.defineProperty(globalThis, 'document', {
        value: { documentElement },
        configurable: true,
    });

    syncBrandingAttributes('blue', 'poppins-inter');

    assert.equal(documentElement.dataset.colorTheme, 'blue');
    assert.equal(documentElement.dataset.fontPair, 'poppins-inter');

    Reflect.deleteProperty(globalThis, 'document');
});

test('builds every color preset from a complete Tailwind ramp', async () => {
    const css = await readSource('resources/css/app.css');

    for (const preset of colorThemePresets) {
        const ramp = blockFor(css, `[data-color-theme='${preset}']`);

        for (const token of rampTokens) {
            assert.ok(ramp.includes(token), `${preset} is missing ${token}`);
        }

        assert.match(ramp, new RegExp(`var\\(--color-${preset}-`));
    }
});

test('maps brand tokens while keeping application surfaces neutral', async () => {
    const css = await readSource('resources/css/app.css');
    const light = blockFor(css, '[data-color-theme]');
    const darkSelector =
        ':is(.dark[data-color-theme], .dark [data-color-theme])';
    const dark = blockFor(css, darkSelector);

    for (const token of brandTokens) {
        assert.ok(light.includes(token), `light mode is missing ${token}`);
        assert.ok(dark.includes(token), `dark mode is missing ${token}`);
    }

    for (const token of neutralSurfaceTokens) {
        assert.ok(
            !light.includes(token),
            `light theme must not override neutral ${token}`,
        );
        assert.ok(
            !dark.includes(token),
            `dark theme must not override neutral ${token}`,
        );
    }

    assert.match(darkSelector, /\.dark\[data-color-theme\]/);
    assert.match(darkSelector, /\.dark \[data-color-theme\]/);
    assert.doesNotMatch(light, /--destructive:/);
    assert.doesNotMatch(dark, /--destructive:/);
});

test('ships heading and body stacks for every font pair preset', async () => {
    const css = await readSource('resources/css/app.css');

    assert.match(css, /--font-sans:\s*var\(--app-font-body\)/);
    assert.match(css, /--font-heading:\s*var\(--app-font-heading\)/);

    for (const preset of fontPairPresets) {
        const block = blockFor(css, `[data-font-pair='${preset}']`);

        assert.match(block, /--app-font-heading:\s*\S/);
        assert.match(block, /--app-font-body:\s*\S/);
    }
});

test('colors every lucide icon from the active theme tokens', async () => {
    const css = await readSource('resources/css/app.css');

    assert.match(css, /:where\(svg\.lucide\)\s*\{\s*color:\s*var\(--primary\)/);
    assert.match(
        css,
        /:where\(\[data-slot='sidebar'\]\)\s*:where\(svg\.lucide\)\s*\{\s*color:\s*var\(--sidebar-primary\)/,
    );
});

test('lets controls and semantic surfaces keep their own icon color', async () => {
    const css = await readSource('resources/css/app.css');

    /* Zero-specificity rules lose to any explicit color utility on the icon. */
    assert.doesNotMatch(css, /^\s*svg\.lucide\s*\{/m);
    assert.match(css, /\[class~='text-primary-foreground'\]/);
    assert.match(css, /\[class~='text-white'\]/);
    assert.match(
        css,
        /\[data-slot='button'\]:not\(\s*\[data-variant='ghost'\]/,
    );

    /* Solid buttons and destructive alerts must not opt into the brand color. */
    assert.doesNotMatch(
        await readSource('resources/js/pages/master-data/users/Index.vue'),
        /<Plus[^>]*text-primary/,
    );
    assert.doesNotMatch(
        await readSource('resources/js/components/AlertError.vue'),
        /<AlertCircle[^>]*text-primary/,
    );
});

test('no longer references the removed primary color setting', async () => {
    for (const file of [
        'resources/css/app.css',
        'resources/js/lib/branding.ts',
        'resources/js/types/branding.ts',
        'resources/views/app.blade.php',
    ]) {
        assert.doesNotMatch(await readSource(file), /primaryColor/);
    }
});
