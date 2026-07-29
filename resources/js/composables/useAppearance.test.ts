import { mount } from '@vue/test-utils';
import { beforeEach, expect, it, vi } from 'vitest';
import { defineComponent, nextTick } from 'vue';
import type { Appearance } from '@/types';

type MediaQueryListener = (event: MediaQueryListEvent) => void;

const createMatchMedia = (prefersDark = false) => {
    let matches = prefersDark;
    const listeners = new Set<MediaQueryListener>();

    const matchMedia = vi.fn(
        (query: string): MediaQueryList =>
            ({
                matches,
                media: query,
                onchange: null,
                addEventListener: vi.fn(
                    (_event: string, listener: MediaQueryListener) => {
                        listeners.add(listener);
                    },
                ),
                removeEventListener: vi.fn(
                    (_event: string, listener: MediaQueryListener) => {
                        listeners.delete(listener);
                    },
                ),
                addListener: vi.fn(),
                removeListener: vi.fn(),
                dispatchEvent: vi.fn(),
            }) as MediaQueryList,
    );

    return {
        matchMedia,
        setMatches(value: boolean) {
            matches = value;

            for (const listener of listeners) {
                listener({ matches: value } as MediaQueryListEvent);
            }
        },
    };
};

const mountComposable = async () => {
    const { useAppearance } = await import('./useAppearance');
    let composable: ReturnType<typeof useAppearance> | undefined;

    const wrapper = mount(
        defineComponent({
            setup() {
                composable = useAppearance();

                return () => null;
            },
        }),
    );

    await nextTick();

    if (!composable) {
        throw new Error('Appearance composable was not initialized.');
    }

    return { composable, wrapper };
};

beforeEach(() => {
    vi.restoreAllMocks();
    vi.unstubAllGlobals();
    vi.resetModules();
    localStorage.clear();
    document.cookie = 'appearance=;path=/;max-age=0';
    document.documentElement.classList.remove('dark');
});

it.each<Appearance>(['light', 'dark', 'system'])(
    'persists the %s appearance to local storage and the SSR cookie',
    async (value) => {
        const media = createMatchMedia();
        vi.stubGlobal('matchMedia', media.matchMedia);
        const setItem = vi.spyOn(Storage.prototype, 'setItem');
        const { composable } = await mountComposable();

        composable.updateAppearance(value);

        expect(setItem).toHaveBeenCalledOnce();
        expect(setItem).toHaveBeenCalledWith('appearance', value);
        expect(document.cookie).toContain(`appearance=${value}`);
    },
);

it('applies and removes the dark class for explicit appearances', async () => {
    const media = createMatchMedia();
    vi.stubGlobal('matchMedia', media.matchMedia);
    const { composable } = await mountComposable();

    composable.updateAppearance('dark');
    expect(document.documentElement.classList.contains('dark')).toBe(true);

    composable.updateAppearance('light');
    expect(document.documentElement.classList.contains('dark')).toBe(false);
});

it.each([
    [true, 'dark'],
    [false, 'light'],
] as const)(
    'resolves a %s system preference to %s',
    async (prefersDark, expectedAppearance) => {
        const media = createMatchMedia(prefersDark);
        vi.stubGlobal('matchMedia', media.matchMedia);
        const { composable } = await mountComposable();

        expect(composable.resolvedAppearance.value).toBe(expectedAppearance);
    },
);

it('updates the system theme when the preferred color scheme changes', async () => {
    const media = createMatchMedia();
    vi.stubGlobal('matchMedia', media.matchMedia);
    localStorage.setItem('appearance', 'system');
    const { initializeTheme } = await import('./useAppearance');

    initializeTheme();
    expect(document.documentElement.classList.contains('dark')).toBe(false);

    media.setMatches(true);
    expect(document.documentElement.classList.contains('dark')).toBe(true);
});

it('restores the stored appearance when mounted', async () => {
    const media = createMatchMedia();
    vi.stubGlobal('matchMedia', media.matchMedia);
    localStorage.setItem('appearance', 'dark');
    const { composable } = await mountComposable();

    expect(composable.appearance.value).toBe('dark');
    expect(composable.resolvedAppearance.value).toBe('dark');
});
