<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import ChatSettingsController from '@/actions/App/Http/Controllers/Settings/ChatSettingsController';
import InputError from '@/components/InputError.vue';
import PageWrapper from '@/components/PageWrapper.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { translate, useTranslations } from '@/composables/useTranslations';
import { index as settingsIndex } from '@/routes/settings';
import { edit } from '@/routes/settings/chat';

type ChatSettings = {
    chatEnabled: boolean;
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
                <div class="grid gap-2">
                    <input
                        type="hidden"
                        name="chatEnabled"
                        :value="chatEnabled ? '1' : '0'"
                    />
                    <div class="flex items-center justify-between gap-4">
                        <Label for="chatEnabled">
                            {{ t('setting.chat.label.chat_enabled') }}
                        </Label>
                        <Switch
                            id="chatEnabled"
                            v-model="chatEnabled"
                            data-test="chat-enabled-switch"
                            :aria-invalid="Boolean(errors.chatEnabled)"
                            class="aria-invalid:border-destructive aria-invalid:ring-3 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40"
                            @update:model-value="validate('chatEnabled')"
                        />
                    </div>
                    <p class="text-sm text-muted-foreground">
                        {{ t('setting.chat.help.chat_enabled') }}
                    </p>
                    <InputError :message="errors.chatEnabled" />
                </div>

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
