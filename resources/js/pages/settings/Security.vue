<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { TriangleAlert } from '@lucide/vue';
import { ref } from 'vue';
import SecuritySettingsController from '@/actions/App/Http/Controllers/Settings/SecuritySettingsController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { translate, useTranslations } from '@/composables/useTranslations';
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
    <Head :title="t('setting.security.title')" />

    <h1 class="sr-only">{{ t('setting.security.title') }}</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            :title="t('setting.security.title')"
            :description="t('setting.security.description')"
        />

        <Form
            v-bind="SecuritySettingsController.update.form()"
            :options="{ preserveScroll: true }"
            class="space-y-6"
            v-slot="{ errors, processing, validate, validating }"
        >
            <div class="grid gap-2">
                <Label for="maxFailedLoginAttempts">
                    {{ t('setting.security.label.max_failed_login_attempts') }}
                </Label>
                <Input
                    id="maxFailedLoginAttempts"
                    type="number"
                    inputmode="numeric"
                    min="1"
                    max="20"
                    class="mt-1 block w-full"
                    name="maxFailedLoginAttempts"
                    :default-value="settings.maxFailedLoginAttempts"
                    required
                    @change="validate('maxFailedLoginAttempts')"
                />
                <p class="text-sm text-muted-foreground">
                    {{ t('setting.security.help.max_failed_login_attempts') }}
                </p>
                <InputError
                    class="mt-2"
                    :message="errors.maxFailedLoginAttempts"
                />
            </div>

            <div class="grid gap-2">
                <Label for="suspensionDurationMinutes">
                    {{
                        t('setting.security.label.suspension_duration_minutes')
                    }}
                </Label>
                <Input
                    id="suspensionDurationMinutes"
                    type="number"
                    inputmode="numeric"
                    min="1"
                    max="1440"
                    class="mt-1 block w-full"
                    name="suspensionDurationMinutes"
                    :default-value="settings.suspensionDurationMinutes"
                    required
                    @change="validate('suspensionDurationMinutes')"
                />
                <p class="text-sm text-muted-foreground">
                    {{ t('setting.security.help.suspension_duration_minutes') }}
                </p>
                <InputError
                    class="mt-2"
                    :message="errors.suspensionDurationMinutes"
                />
            </div>

            <div class="grid gap-2">
                <input
                    type="hidden"
                    name="maintenanceEnabled"
                    :value="maintenanceEnabled ? '1' : '0'"
                />
                <div class="flex items-center justify-between gap-4">
                    <Label for="maintenanceEnabled">
                        {{ t('setting.security.label.maintenance_enabled') }}
                    </Label>
                    <Switch
                        id="maintenanceEnabled"
                        v-model="maintenanceEnabled"
                        @update:model-value="validate('maintenanceEnabled')"
                    />
                </div>
                <p class="text-sm text-muted-foreground">
                    {{ t('setting.security.help.maintenance_enabled') }}
                </p>
                <InputError class="mt-2" :message="errors.maintenanceEnabled" />
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
    </div>
</template>
