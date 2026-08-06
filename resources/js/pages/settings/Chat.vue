<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import ChatSettingsController from '@/actions/App/Http/Controllers/Settings/ChatSettingsController';
import BooleanField from '@/components/BooleanField.vue';
import PageWrapper from '@/components/PageWrapper.vue';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { useTranslations } from '@/composables/useTranslations';
import { breadcrumbLayout } from '@/lib/breadcrumbs';
import { index as settingsIndex } from '@/routes/settings';
import { edit } from '@/routes/settings/chat';

type ChatSettings = {
    chatEnabled: boolean;
    imageUploadsEnabled: boolean;
};

const props = defineProps<{
    settings: ChatSettings;
}>();

defineOptions({
    layout: breadcrumbLayout(() => [
        { key: 'setting.layout.title', href: settingsIndex() },
        { key: 'setting.chat.title', href: edit() },
    ]),
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
                    <BooleanField
                        v-model="chatEnabled"
                        name="chatEnabled"
                        :label="t('setting.chat.label.chat_enabled')"
                        :description="t('setting.chat.help.chat_enabled')"
                        :error="errors.chatEnabled"
                        data-test="chat-enabled-switch"
                        @validate="validate('chatEnabled')"
                    />

                    <BooleanField
                        v-model="imageUploadsEnabled"
                        name="imageUploadsEnabled"
                        :label="t('setting.chat.label.image_uploads_enabled')"
                        :description="
                            t('setting.chat.help.image_uploads_enabled')
                        "
                        :error="errors.imageUploadsEnabled"
                        :disabled="!chatEnabled"
                        data-test="chat-image-uploads-enabled-switch"
                        @validate="validate('imageUploadsEnabled')"
                    />
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
