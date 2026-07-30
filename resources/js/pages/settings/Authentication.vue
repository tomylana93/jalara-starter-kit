<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AuthenticationSettingsController from '@/actions/App/Http/Controllers/Settings/AuthenticationSettingsController';
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
import { Switch } from '@/components/ui/switch';
import { translate, useTranslations } from '@/composables/useTranslations';
import { index as settingsIndex } from '@/routes/settings';
import { edit } from '@/routes/settings/authentication';
import type { SelectOption } from '@/types';

type AuthenticationSettings = {
    requireEmailVerification: boolean;
    passwordPolicy: string;
    sessionLifetimeMinutes: number;
};

type LayoutProps = {
    locale: string;
    fallbackLocale: string;
};

const props = defineProps<{
    settings: AuthenticationSettings;
    passwordPolicyOptions: SelectOption[];
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
                    'setting.authentication.title',
                    layoutProps.locale,
                    layoutProps.fallbackLocale,
                ),
                href: edit(),
            },
        ],
    }),
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
                <div class="grid gap-2">
                    <input
                        type="hidden"
                        name="requireEmailVerification"
                        :value="requireEmailVerification ? '1' : '0'"
                    />
                    <div class="flex items-center justify-between gap-4">
                        <Label for="requireEmailVerification">
                            {{
                                t(
                                    'setting.authentication.label.require_email_verification',
                                )
                            }}
                        </Label>
                        <Switch
                            id="requireEmailVerification"
                            v-model="requireEmailVerification"
                            :aria-invalid="
                                Boolean(errors.requireEmailVerification)
                            "
                            class="aria-invalid:border-destructive aria-invalid:ring-3 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40"
                            @update:model-value="
                                validate('requireEmailVerification')
                            "
                        />
                    </div>
                    <InputError :message="errors.requireEmailVerification" />
                </div>

                <div class="grid gap-2">
                    <Label for="passwordPolicy">
                        {{ t('setting.authentication.label.password_policy') }}
                    </Label>
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
