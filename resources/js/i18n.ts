import { createI18n } from 'vue-i18n';

import {
    messages,
    translationMetadata,
    type AppLocale,
} from '@/locales/generated/messages';

type LocaleConfig = {
    active: string;
    fallback: string;
};

export function createAppI18n(localeConfig: unknown) {
    const { active, fallback } = resolveLocaleConfig(localeConfig);
    const resolvedFallbackLocale = resolveLocale(
        fallback,
        translationMetadata.fallbackLocale,
    );

    return createI18n({
        legacy: false,
        locale: resolveLocale(active, resolvedFallbackLocale),
        fallbackLocale: resolvedFallbackLocale,
        messages,
    });
}

function resolveLocaleConfig(localeConfig: unknown): LocaleConfig {
    if (
        typeof localeConfig === 'object' &&
        localeConfig !== null &&
        'active' in localeConfig &&
        typeof localeConfig.active === 'string' &&
        'fallback' in localeConfig &&
        typeof localeConfig.fallback === 'string'
    ) {
        return {
            active: localeConfig.active,
            fallback: localeConfig.fallback,
        };
    }

    return {
        active: translationMetadata.fallbackLocale,
        fallback: translationMetadata.fallbackLocale,
    };
}

function resolveLocale(locale: string, fallbackLocale: AppLocale): AppLocale {
    return Object.hasOwn(messages, locale)
        ? (locale as AppLocale)
        : fallbackLocale;
}
