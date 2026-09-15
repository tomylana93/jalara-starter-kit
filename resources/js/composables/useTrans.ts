import { useI18n } from 'vue-i18n';

import type {
    AppLocale,
    MessageKey,
    MessageParameters,
    MessageSchema,
} from '@/locales/generated/messages';

type TranslationArguments<Key extends MessageKey> =
    MessageParameters[Key] extends Record<string, never>
        ? [parameters?: MessageParameters[Key]]
        : [parameters: MessageParameters[Key]];

export function useTrans() {
    const { t } = useI18n<{ message: MessageSchema }, AppLocale>({
        useScope: 'global',
    });

    function trans<Key extends MessageKey>(
        key: Key,
        ...[parameters]: TranslationArguments<Key>
    ): string {
        return t(key, parameters ?? {});
    }

    return { trans };
}
