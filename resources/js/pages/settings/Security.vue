<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { TriangleAlert } from '@lucide/vue';
import { ref } from 'vue';
import SecuritySettingsController from '@/actions/App/Http/Controllers/Settings/SecuritySettingsController';
import InputError from '@/components/InputError.vue';
import PageWrapper from '@/components/PageWrapper.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { translate, useTranslations } from '@/composables/useTranslations';
import { index as settingsIndex } from '@/routes/settings';
import { edit } from '@/routes/settings/security';

type SecuritySettings = {
    maxFailedLoginAttempts: number;
    suspensionDurationMinutes: number;
    maintenanceEnabled: boolean;
};

type LayoutProps = {
    locale: string;
    fallbackLocale: string;
};

const props = defineProps<{
    settings: SecuritySettings;
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
                    'setting.security.title',
                    layoutProps.locale,
                    layoutProps.fallbackLocale,
                ),
                href: edit(),
            },
        ],
    }),
});

const { t } = useTranslations();

const maintenanceEnabled = ref(props.settings.maintenanceEnabled);
</script>

<template>
    <div class="contents">
        <Head :title="t('setting.security.title')" />

        <PageWrapper
            :title="t('setting.security.title')"
            :description="t('setting.security.description')"
        >
            <Form
                v-bind="SecuritySettingsController.update.form()"
                :options="{ preserveScroll: true }"
                class="space-y-6"
                v-slot="{ errors, processing, validate, validating }"
            >
                <div class="grid gap-2">
                    <Label for="maxFailedLoginAttempts">
                        {{
                            t(
                                'setting.security.label.max_failed_login_attempts',
                            )
                        }}
                    </Label>
                    <Input
                        id="maxFailedLoginAttempts"
                        type="text"
                        inputmode="numeric"
                        name="maxFailedLoginAttempts"
                        :default-value="settings.maxFailedLoginAttempts"
                        :aria-invalid="Boolean(errors.maxFailedLoginAttempts)"
                        @change="validate('maxFailedLoginAttempts')"
                    />
                    <InputError :message="errors.maxFailedLoginAttempts" />
                </div>

                <div class="grid gap-2">
                    <Label for="suspensionDurationMinutes">
                        {{
                            t(
                                'setting.security.label.suspension_duration_minutes',
                            )
                        }}
                    </Label>
                    <Input
                        id="suspensionDurationMinutes"
                        type="text"
                        inputmode="numeric"
                        name="suspensionDurationMinutes"
                        :default-value="settings.suspensionDurationMinutes"
                        :aria-invalid="
                            Boolean(errors.suspensionDurationMinutes)
                        "
                        @change="validate('suspensionDurationMinutes')"
                    />
                    <InputError :message="errors.suspensionDurationMinutes" />
                </div>

                <div class="grid gap-2">
                    <input
                        type="hidden"
                        name="maintenanceEnabled"
                        :value="maintenanceEnabled ? '1' : '0'"
                    />
                    <div class="flex items-center justify-between gap-4">
                        <Label for="maintenanceEnabled">
                            {{
                                t('setting.security.label.maintenance_enabled')
                            }}
                        </Label>
                        <Switch
                            id="maintenanceEnabled"
                            v-model="maintenanceEnabled"
                            :aria-invalid="Boolean(errors.maintenanceEnabled)"
                            class="aria-invalid:border-destructive aria-invalid:ring-3 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40"
                            @update:model-value="validate('maintenanceEnabled')"
                        />
                    </div>
                    <InputError :message="errors.maintenanceEnabled" />
                </div>

                <Alert>
                    <TriangleAlert class="size-4" />
                    <AlertTitle>
                        {{ t('setting.security.alert.maintenance_title') }}
                    </AlertTitle>
                    <AlertDescription>
                        {{ t('setting.security.alert.maintenance') }}
                    </AlertDescription>
                </Alert>

                <div class="flex items-center gap-4">
                    <Button
                        :disabled="processing || validating"
                        data-test="update-security-settings-button"
                    >
                        {{ t('setting.security.button.save') }}
                    </Button>
                </div>
            </Form>
        </PageWrapper>
    </div>
</template>
