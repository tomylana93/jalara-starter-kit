import { Monitor, Moon, Sun } from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';
import type { Appearance, ResolvedAppearance } from '@/types';
import type { TranslationKey } from '@/types/i18n';

type ThemeOption = {
    value: Appearance;
    icon: LucideIcon;
    labelKey: TranslationKey;
};

export const themeOptions = [
    {
        value: 'light',
        icon: Sun,
        labelKey: 'appearance.enum.theme.light',
    },
    {
        value: 'dark',
        icon: Moon,
        labelKey: 'appearance.enum.theme.dark',
    },
    {
        value: 'system',
        icon: Monitor,
        labelKey: 'appearance.enum.theme.system',
    },
] as const satisfies readonly ThemeOption[];

export const resolveAppearance = (
    stored: Appearance,
    prefersDark: boolean,
): ResolvedAppearance => {
    if (stored === 'system') {
        return prefersDark ? 'dark' : 'light';
    }

    return stored;
};
