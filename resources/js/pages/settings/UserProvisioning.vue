<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { useTemplateRef } from 'vue';
import UserProvisioningSettingsController from '@/actions/App/Http/Controllers/Settings/UserProvisioningSettingsController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { translate, useTranslations } from '@/composables/useTranslations';
import { edit } from '@/routes/settings/user-provisioning';

type LayoutProps = {
    locale: string;
    fallbackLocale: string;
};

defineProps<{
    hasDefaultPassword: boolean;
    passwordRules: string;
}>();

defineOptions({
    layout: (layoutProps: LayoutProps) => ({
        breadcrumbs: [
            {
                title: translate(
                    'setting.user_provisioning.title',
                    layoutProps.locale,
                    layoutProps.fallbackLocale,
                ),
                href: edit(),
            },
        ],
    }),
});

const passwordInput = useTemplateRef('passwordInput');
const { t } = useTranslations();
</script>

<template>
    <Head :title="t('setting.user_provisioning.title')" />

    <h1 class="sr-only">{{ t('setting.user_provisioning.title') }}</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            :title="t('setting.user_provisioning.title')"
            :description="t('setting.user_provisioning.description')"
        />

        <div class="flex items-center gap-3">
            <span class="text-sm font-medium">
                {{ t('setting.user_provisioning.label.status') }}
            </span>
            <Badge
                :variant="hasDefaultPassword ? 'default' : 'secondary'"
                data-test="default-password-status"
            >
                {{
                    hasDefaultPassword
                        ? t('setting.user_provisioning.status.configured')
                        : t('setting.user_provisioning.status.not_configured')
                }}
            </Badge>
        </div>

        <Form
            v-bind="UserProvisioningSettingsController.update.form()"
            reset-on-success
            @error="() => passwordInput?.focus()"
            :options="{ preserveScroll: true }"
            class="space-y-6"
            v-slot="{ errors, processing, validate, validating }"
        >
            <div class="grid gap-2">
                <Label for="defaultPassword">
                    {{ t('setting.user_provisioning.label.default_password') }}
                </Label>
                <PasswordInput
                    id="defaultPassword"
                    ref="passwordInput"
                    name="defaultPassword"
                    autocomplete="new-password"
                    :aria-invalid="Boolean(errors.defaultPassword)"
                    :passwordrules="passwordRules"
                    @change="validate('defaultPassword')"
                    :placeholder="
                        t(
                            'setting.user_provisioning.placeholder.default_password',
                        )
                    "
                />
                <InputError :message="errors.defaultPassword" />
            </div>

            <div class="grid gap-2">
                <Label for="defaultPassword_confirmation">
                    {{
                        t(
                            'setting.user_provisioning.label.default_password_confirmation',
                        )
                    }}
                </Label>
                <PasswordInput
                    id="defaultPassword_confirmation"
                    name="defaultPassword_confirmation"
                    autocomplete="new-password"
                    :aria-invalid="Boolean(errors.defaultPassword_confirmation)"
                    :passwordrules="passwordRules"
                    @change="
                        validate({
                            only: [
                                'defaultPassword',
                                'defaultPassword_confirmation',
                            ],
                        })
                    "
                    :placeholder="
                        t(
                            'setting.user_provisioning.placeholder.default_password_confirmation',
                        )
                    "
                />
                <InputError :message="errors.defaultPassword_confirmation" />
            </div>

            <div class="flex items-center gap-4">
                <Button
                    :disabled="processing || validating"
                    data-test="update-default-password-button"
                >
                    {{ t('setting.user_provisioning.button.save') }}
                </Button>
            </div>
        </Form>

        <template v-if="hasDefaultPassword">
            <Separator />

            <AlertDialog>
                <AlertDialogTrigger as-child>
                    <Button
                        variant="destructive"
                        class="w-fit"
                        data-test="remove-default-password-button"
                    >
                        {{ t('setting.user_provisioning.button.remove') }}
                    </Button>
                </AlertDialogTrigger>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            {{
                                t(
                                    'setting.user_provisioning.confirmation.title',
                                )
                            }}
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            {{
                                t(
                                    'setting.user_provisioning.confirmation.description',
                                )
                            }}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>
                            {{ t('setting.user_provisioning.button.cancel') }}
                        </AlertDialogCancel>
                        <Form
                            v-bind="
                                UserProvisioningSettingsController.destroy.form()
                            "
                            :options="{ preserveScroll: true }"
                            v-slot="{ processing }"
                        >
                            <AlertDialogAction
                                type="submit"
                                variant="destructive"
                                :disabled="processing"
                                data-test="confirm-remove-default-password-button"
                            >
                                {{
                                    t(
                                        'setting.user_provisioning.button.confirm_remove',
                                    )
                                }}
                            </AlertDialogAction>
                        </Form>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </template>
    </div>
</template>
