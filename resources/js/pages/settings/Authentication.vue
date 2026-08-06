<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AuthenticationSettingsController from '@/actions/App/Http/Controllers/Settings/AuthenticationSettingsController';
import BooleanField from '@/components/BooleanField.vue';
import InputError from '@/components/InputError.vue';
import PageWrapper from '@/components/PageWrapper.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslations } from '@/composables/useTranslations';
import { breadcrumbLayout } from '@/lib/breadcrumbs';
import { index as settingsIndex } from '@/routes/settings';
import { edit } from '@/routes/settings/authentication';
import type { SelectOption } from '@/types';

type AuthenticationSettings = {
    requireEmailVerification: boolean;
    passwordPolicy: string;
    sessionLifetimeMinutes: number;
};

const props = defineProps<{
    settings: AuthenticationSettings;
    passwordPolicyOptions: SelectOption[];
}>();

defineOptions({
    layout: breadcrumbLayout(() => [
        { key: 'setting.layout.title', href: settingsIndex() },
        { key: 'setting.authentication.title', href: edit() },
    ]),
});

const { t } = useTranslations();

const requireEmailVerification = ref(props.settings.requireEmailVerification);
const passwordPolicy = ref(props.settings.passwordPolicy);

const passwordPolicyLabel = computed(
    () =>
        props.passwordPolicyOptions.find(
            (option) => option.value === passwordPolicy.value,
        )?.label ?? passwordPolicy.value,
);
</script>

<template>
    <div class="contents">
        <Head :title="t('setting.authentication.title')" />

        <PageWrapper
            :title="t('setting.authentication.title')"
            :description="t('setting.authentication.description')"
        >
            <Form
                v-bind="AuthenticationSettingsController.update.form()"
                :options="{ preserveScroll: true }"
                class="space-y-6"
                v-slot="{ errors, processing, validate, validating }"
            >
                <BooleanField
                    v-model="requireEmailVerification"
                    name="requireEmailVerification"
                    :label="
                        t(
                            'setting.authentication.label.require_email_verification',
                        )
                    "
                    :description="
                        t(
                            'setting.authentication.help.require_email_verification',
                        )
                    "
                    :error="errors.requireEmailVerification"
                    @validate="validate('requireEmailVerification')"
                />

                <div class="grid gap-2">
                    <Label for="passwordPolicy">
                        {{ t('setting.authentication.label.password_policy') }}
                    </Label>
                    <p class="text-sm text-muted-foreground">
                        {{ t('setting.authentication.help.password_policy') }}
                    </p>
                    <input
                        type="hidden"
                        name="passwordPolicy"
                        :value="passwordPolicy"
                    />
                    <Select
                        v-model="passwordPolicy"
                        @update:model-value="validate('passwordPolicy')"
                    >
                        <SelectTrigger
                            id="passwordPolicy"
                            class="w-full"
                            :aria-invalid="Boolean(errors.passwordPolicy)"
                        >
                            <SelectValue>{{ passwordPolicyLabel }}</SelectValue>
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in passwordPolicyOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.passwordPolicy" />
                </div>

                <div class="grid gap-2">
                    <Label for="sessionLifetimeMinutes">
                        {{
                            t(
                                'setting.authentication.label.session_lifetime_minutes',
                            )
                        }}
                    </Label>
                    <p class="text-sm text-muted-foreground">
                        {{
                            t(
                                'setting.authentication.help.session_lifetime_minutes',
                            )
                        }}
                    </p>
                    <Input
                        id="sessionLifetimeMinutes"
                        type="text"
                        inputmode="numeric"
                        name="sessionLifetimeMinutes"
                        :default-value="settings.sessionLifetimeMinutes"
                        :aria-invalid="Boolean(errors.sessionLifetimeMinutes)"
                        @change="validate('sessionLifetimeMinutes')"
                    />
                    <InputError :message="errors.sessionLifetimeMinutes" />
                </div>

                <div class="flex items-center gap-4">
                    <Button
                        :disabled="processing || validating"
                        data-test="update-authentication-settings-button"
                    >
                        {{ t('setting.authentication.button.save') }}
                    </Button>
                </div>
            </Form>
        </PageWrapper>
    </div>
</template>
