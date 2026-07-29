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
    fontPresets,
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
    '--sidebar-primary:',
    '--sidebar-primary-foreground:',
    '--sidebar-ring:',
    '--chart-1:',
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

test('drives the visible identity from the branding company name', async () => {
    /*
     * BrandIdentity is the single owner of the visible identity: it resolves the
     * identity mode, the dark variants, and the static fallback. Every branded
     * surface must render through it rather than reaching for the name itself.
     */
    const identity = await readSource(
        'resources/js/components/BrandIdentity.vue',
    );

    assert.match(identity, /branding\.companyName/);
    assert.doesNotMatch(identity, /props\.name/);

    for (const file of [
        'resources/js/components/AppLogo.vue',
        'resources/js/layouts/auth/AuthSplitLayout.vue',
    ]) {
        const source = await readSource(file);

        assert.match(source, /BrandIdentity/);
        assert.doesNotMatch(source, /props\.name/);
    }

    const entry = await readSource('resources/js/app.ts');

    assert.match(entry, /applicationTitle\(/);
    assert.match(entry, /page\.props\.name/);
    assert.doesNotMatch(entry, /branding\?\.companyName/);
});

test('renders the branding footer text in every layout', async () => {
    const footer = await readSource('resources/js/components/AppFooter.vue');

    assert.match(footer, /branding\.footerText/);

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

    syncBrandingAttributes('blue', 'system-mono');

    assert.equal(documentElement.dataset.colorTheme, 'blue');
    assert.equal(documentElement.dataset.fontPreset, 'system-mono');

    Reflect.deleteProperty(globalThis, 'document');
});

test('ships light and dark tokens for every color preset', async () => {
    const css = await readSource('resources/css/app.css');

    for (const preset of colorThemePresets) {
        const light = blockFor(css, `[data-color-theme='${preset}']`);
        const dark = blockFor(css, `.dark[data-color-theme='${preset}']`);

        for (const token of brandTokens) {
            assert.ok(
                light.includes(token),
                `${preset} is missing ${token} in light mode`,
            );
            assert.ok(
                dark.includes(token),
                `${preset} is missing ${token} in dark mode`,
            );
        }
    }
});

test('ships a font stack for every font preset', async () => {
    const css = await readSource('resources/css/app.css');

    assert.match(css, /--font-sans:\s*var\(--app-font-family\)/);

    for (const preset of fontPresets) {
        const block = blockFor(css, `[data-font-preset='${preset}']`);

        assert.match(block, /--app-font-family:\s*\S/);
    }
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
