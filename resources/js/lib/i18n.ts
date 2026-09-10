import { createI18n } from 'vue-i18n';
import type { SupportedLocale, TranslationKey } from '@/types/i18n';

const bundles = import.meta.glob<Record<TranslationKey, string>>(
    '../i18n/*.json',
    {
        eager: true,
        import: 'default',
    },
);

const fallbackLocale: SupportedLocale = 'en';

export const i18n = createI18n({
    legacy: false,
    locale: fallbackLocale,
    fallbackLocale,
    messages: Object.fromEntries(
        Object.entries(bundles).map(([path, messages]) => [
            localeFromPath(path),
            messages,
        ]),
    ),
    flatJson: true,
    fallbackWarn: false,
    missingWarn: import.meta.env.DEV,
});

export function initializeI18n(): void {
    const locale = requestedLocale();

    setActiveLocale(locale);
}

export function setLocale(locale: SupportedLocale): void {
    setActiveLocale(locale);
}

export function t(
    key: TranslationKey,
    replacements: Record<string, unknown> = {},
): string {
    return i18n.global.t(key, replacements);
}

function setActiveLocale(locale: SupportedLocale): void {
    i18n.global.locale.value = locale;

    if (typeof document !== 'undefined') {
        document.documentElement.lang = locale.replace('_', '-');
    }
}

function requestedLocale(): SupportedLocale {
    if (typeof document === 'undefined') {
        return fallbackLocale;
    }

    const locale = document.documentElement.lang.replace('-', '_');

    return isSupportedLocale(locale) ? locale : fallbackLocale;
}

function isSupportedLocale(locale: string): locale is SupportedLocale {
    return bundles[`../i18n/${locale}.json`] !== undefined;
}

function localeFromPath(path: string): SupportedLocale {
    const locale = path.match(/\/([^/]+)\.json$/)?.[1];

    if (locale === undefined || !isSupportedLocale(locale)) {
        throw new Error(`Invalid translation bundle path [${path}].`);
    }

    return locale;
}
