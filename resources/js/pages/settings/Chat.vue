<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import ChatSettingsController from '@/actions/App/Http/Controllers/Settings/ChatSettingsController';
import InputError from '@/components/InputError.vue';
import PageWrapper from '@/components/PageWrapper.vue';
import { Button } from '@/components/ui/button';
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Switch } from '@/components/ui/switch';
import { translate, useTranslations } from '@/composables/useTranslations';
import { index as settingsIndex } from '@/routes/settings';
import { edit } from '@/routes/settings/chat';

type ChatSettings = {
    chatEnabled: boolean;
    imageUploadsEnabled: boolean;
};

type LayoutProps = {
    locale: string;
    fallbackLocale: string;
};

const props = defineProps<{
    settings: ChatSettings;
}>();

defineOptions({
    layout: (layoutProps: LayoutProps) => ({
        breadcrumbs: [
            {
                title: translate(
                    'setting.layout.title',
                    layoutProps.locale,
                    layoutProps.fallbackLocale,
                ),
                href: settingsIndex(),
            },
            {
                title: translate(
                    'setting.chat.title',
                    layoutProps.locale,
                    layoutProps.fallbackLocale,
                ),
                href: edit(),
            },
        ],
    }),
});

const { t } = useTranslations();

const chatEnabled = ref(props.settings.chatEnabled);
const imageUploadsEnabled = ref(props.settings.imageUploadsEnabled);
</script>

<template>
    <div class="contents">
        <Head :title="t('setting.chat.title')" />

        <PageWrapper
            :title="t('setting.chat.title')"
            :description="t('setting.chat.description')"
        >
            <Form
                v-bind="ChatSettingsController.update.form()"
                :options="{ preserveScroll: true }"
                class="space-y-6"
                v-slot="{ errors, processing, validate, validating }"
            >
                <FieldGroup>
                    <input
                        type="hidden"
                        name="chatEnabled"
                        :value="chatEnabled ? '1' : '0'"
                    />
                    <Field orientation="horizontal">
                        <FieldContent>
                            <FieldLabel for="chatEnabled">
                                {{ t('setting.chat.label.chat_enabled') }}
                            </FieldLabel>
                            <FieldDescription>
                                {{ t('setting.chat.help.chat_enabled') }}
                            </FieldDescription>
                            <InputError :message="errors.chatEnabled" />
                        </FieldContent>
                        <Switch
                            id="chatEnabled"
                            v-model="chatEnabled"
                            data-test="chat-enabled-switch"
                            :aria-invalid="Boolean(errors.chatEnabled)"
                            class="aria-invalid:border-destructive aria-invalid:ring-3 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40"
                            @update:model-value="validate('chatEnabled')"
                        />
                    </Field>

                    <input
                        type="hidden"
                        name="imageUploadsEnabled"
                        :value="imageUploadsEnabled ? '1' : '0'"
                    />
                    <Field
                        orientation="horizontal"
                        :data-disabled="!chatEnabled"
                    >
                        <FieldContent>
                            <FieldLabel for="imageUploadsEnabled">
                                {{
                                    t(
                                        'setting.chat.label.image_uploads_enabled',
                                    )
                                }}
                            </FieldLabel>
                            <FieldDescription>
                                {{
                                    t('setting.chat.help.image_uploads_enabled')
                                }}
                            </FieldDescription>
                            <InputError :message="errors.imageUploadsEnabled" />
                        </FieldContent>
                        <Switch
                            id="imageUploadsEnabled"
                            v-model="imageUploadsEnabled"
                            data-test="chat-image-uploads-enabled-switch"
                            :disabled="!chatEnabled"
                            :aria-invalid="Boolean(errors.imageUploadsEnabled)"
                            class="aria-invalid:border-destructive aria-invalid:ring-3 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40"
                            @update:model-value="
                                validate('imageUploadsEnabled')
                            "
                        />
                    </Field>
                </FieldGroup>

                <div class="flex items-center gap-4">
                    <Button
                        :disabled="processing || validating"
                        data-test="update-chat-settings-button"
                    >
                        {{ t('setting.chat.button.save') }}
                    </Button>
                </div>
            </Form>
        </PageWrapper>
    </div>
</template>
