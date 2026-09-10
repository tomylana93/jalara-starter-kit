import { createI18n } from 'vue-i18n';
import type { SupportedLocale, TranslationKey } from '@/types/i18n';

const loaders = import.meta.glob<{
    default: Record<TranslationKey, string>;
}>('../i18n/*.json');

const loadedLocales = new Set<SupportedLocale>();

const fallbackLocale: SupportedLocale = 'en';

export const i18n = createI18n({
    legacy: false,
    locale: fallbackLocale,
    fallbackLocale,
    messages: {},
    flatJson: true,
    fallbackWarn: false,
    missingWarn: import.meta.env.DEV,
});

export async function initializeI18n(): Promise<void> {
    const locale = requestedLocale();

    await Promise.all([loadLocale(fallbackLocale), loadLocale(locale)]);
    setActiveLocale(locale);
}

export async function setLocale(locale: SupportedLocale): Promise<void> {
    await loadLocale(locale);
    setActiveLocale(locale);
}

export function t(
    key: TranslationKey,
    replacements: Record<string, unknown> = {},
): string {
    return i18n.global.t(key, replacements);
}

async function loadLocale(locale: SupportedLocale): Promise<void> {
    if (loadedLocales.has(locale)) {
        return;
    }

    const loader = loaders[`../i18n/${locale}.json`];

    if (loader === undefined) {
        throw new Error(`Missing translation bundle for locale [${locale}].`);
    }

    const { default: messages } = await loader();

    i18n.global.setLocaleMessage(locale, messages);
    loadedLocales.add(locale);
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
    return loaders[`../i18n/${locale}.json`] !== undefined;
}
