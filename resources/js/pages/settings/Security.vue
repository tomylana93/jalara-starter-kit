<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { TriangleAlert } from '@lucide/vue';
import { ref } from 'vue';
import SecuritySettingsController from '@/actions/App/Http/Controllers/Settings/SecuritySettingsController';
import BooleanField from '@/components/BooleanField.vue';
import InputError from '@/components/InputError.vue';
import PageWrapper from '@/components/PageWrapper.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';
import { breadcrumbLayout } from '@/lib/breadcrumbs';
import { index as settingsIndex } from '@/routes/settings';
import { edit } from '@/routes/settings/security';

type SecuritySettings = {
    maxFailedLoginAttempts: number;
    suspensionDurationMinutes: number;
    maintenanceEnabled: boolean;
};

const props = defineProps<{
    settings: SecuritySettings;
}>();

defineOptions({
    layout: breadcrumbLayout(() => [
        { key: 'setting.layout.title', href: settingsIndex() },
        { key: 'setting.security.title', href: edit() },
    ]),
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
                    <p class="text-sm text-muted-foreground">
                        {{
                            t('setting.security.help.max_failed_login_attempts')
                        }}
                    </p>
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
                    <p class="text-sm text-muted-foreground">
                        {{
                            t(
                                'setting.security.help.suspension_duration_minutes',
                            )
                        }}
                    </p>
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

                <BooleanField
                    v-model="maintenanceEnabled"
                    name="maintenanceEnabled"
                    :label="t('setting.security.label.maintenance_enabled')"
                    :description="
                        t('setting.security.help.maintenance_enabled')
                    "
                    :error="errors.maintenanceEnabled"
                    @validate="validate('maintenanceEnabled')"
                />

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
