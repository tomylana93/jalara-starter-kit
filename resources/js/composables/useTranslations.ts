import { usePage } from '@inertiajs/vue3';

type TranslationNode =
    | string
    | number
    | boolean
    | null
    | TranslationNode[]
    | { [key: string]: TranslationNode };

type TranslationReplacements = Record<string, string | number>;
type LocaleTranslations = Record<string, TranslationNode>;

const localeModules = import.meta.glob('../generated/lang/*.json', {
    eager: true,
    import: 'default',
}) as Record<string, LocaleTranslations>;

const translations = Object.fromEntries(
    Object.entries(localeModules).map(([filename, messages]) => {
        const locale = filename.match(/\/([^/]+)\.json$/)?.[1];

        if (!locale) {
            throw new Error(
                `Unable to determine locale from generated language file: ${filename}`,
            );
        }

        return [locale, messages];
    }),
) as Record<string, LocaleTranslations>;

const findTranslation = (
    locale: string,
    key: string,
): TranslationNode | undefined => {
    let translation: TranslationNode | undefined = translations[locale];

    for (const segment of key.split('.')) {
        if (
            !translation ||
            typeof translation !== 'object' ||
            Array.isArray(translation)
        ) {
            return undefined;
        }

        translation = translation[segment];
    }

    return translation;
};

const replaceParameters = (
    translation: string,
    replacements: TranslationReplacements,
): string =>
    translation.replace(/:([a-z_][a-z0-9_]*)/gi, (placeholder, key: string) =>
        key in replacements ? String(replacements[key]) : placeholder,
    );

export const translate = (
    key: string,
    locale: string,
    fallbackLocale: string,
    replacements: TranslationReplacements = {},
): string => {
    const translation =
        findTranslation(locale, key) ?? findTranslation(fallbackLocale, key);

    if (typeof translation !== 'string') {
        return key;
    }

    return replaceParameters(translation, replacements);
};

export const useTranslations = () => {
    const page = usePage();

    return {
        t: (key: string, replacements: TranslationReplacements = {}): string =>
            translate(
                key,
                page.props.locale,
                page.props.fallbackLocale,
                replacements,
            ),
    };
};
