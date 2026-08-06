<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import MailSettingsController from '@/actions/App/Http/Controllers/Settings/MailSettingsController';
import InputError from '@/components/InputError.vue';
import PageWrapper from '@/components/PageWrapper.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { useTranslations } from '@/composables/useTranslations';
import { breadcrumbLayout } from '@/lib/breadcrumbs';
import { index as settingsIndex } from '@/routes/settings';
import { edit } from '@/routes/settings/mail';

type MailSettings = {
    fromName: string;
    fromAddress: string;
};

defineProps<{
    settings: MailSettings;
}>();

defineOptions({
    layout: breadcrumbLayout(() => [
        { key: 'setting.layout.title', href: settingsIndex() },
        { key: 'setting.mail.title', href: edit() },
    ]),
});

const { t } = useTranslations();
</script>

<template>
    <div class="contents">
        <Head :title="t('setting.mail.title')" />

        <PageWrapper
            :title="t('setting.mail.title')"
            :description="t('setting.mail.description')"
            content-class="space-y-6"
        >
            <Form
                v-bind="MailSettingsController.update.form()"
                :options="{ preserveScroll: true }"
                class="space-y-6"
                v-slot="{ errors, processing, validate, validating }"
            >
                <div class="grid gap-2">
                    <Label for="fromName">
                        {{ t('setting.mail.label.from_name') }}
                    </Label>
                    <Input
                        id="fromName"
                        name="fromName"
                        :default-value="settings.fromName"
                        :aria-invalid="Boolean(errors.fromName)"
                        @change="validate('fromName')"
                        :placeholder="t('setting.mail.placeholder.from_name')"
                    />
                    <InputError :message="errors.fromName" />
                </div>

                <div class="grid gap-2">
                    <Label for="fromAddress">
                        {{ t('setting.mail.label.from_address') }}
                    </Label>
                    <Input
                        id="fromAddress"
                        type="text"
                        inputmode="email"
                        name="fromAddress"
                        :default-value="settings.fromAddress"
                        :aria-invalid="Boolean(errors.fromAddress)"
                        autocomplete="email"
                        @change="validate('fromAddress')"
                        :placeholder="
                            t('setting.mail.placeholder.from_address')
                        "
                    />
                    <InputError :message="errors.fromAddress" />
                </div>

                <div class="flex items-center gap-4">
                    <Button
                        :disabled="processing || validating"
                        data-test="update-mail-settings-button"
                    >
                        {{ t('setting.mail.button.save') }}
                    </Button>
                </div>
            </Form>

            <Separator />

            <Form
                v-bind="MailSettingsController.test.form()"
                :options="{ preserveScroll: true }"
                class="space-y-4"
                v-slot="{ processing }"
            >
                <p class="text-sm text-muted-foreground">
                    {{ t('setting.mail.help.test') }}
                </p>

                <Button
                    variant="secondary"
                    :disabled="processing"
                    data-test="send-test-mail-button"
                >
                    {{ t('setting.mail.button.test') }}
                </Button>
            </Form>
        </PageWrapper>
    </div>
</template>
