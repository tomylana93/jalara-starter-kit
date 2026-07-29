<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import BrandingSettingsController from '@/actions/App/Http/Controllers/Settings/BrandingSettingsController';
import InputError from '@/components/InputError.vue';
import PageWrapper from '@/components/PageWrapper.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Textarea } from '@/components/ui/textarea';
import { translate, useTranslations } from '@/composables/useTranslations';
import { index as settingsIndex } from '@/routes/settings';
import { edit } from '@/routes/settings/branding';
import type { SelectOption } from '@/types';

type BrandingSettings = {
    companyName: string;
    footerText: string | null;
    authLayout: string;
    appLayout: string;
    colorTheme: string;
    fontPreset: string;
};

type LayoutProps = {
    locale: string;
    fallbackLocale: string;
};

const props = defineProps<{
    settings: BrandingSettings;
    authLayoutOptions: SelectOption[];
    appLayoutOptions: SelectOption[];
    colorThemeOptions: SelectOption[];
    fontPresetOptions: SelectOption[];
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
                    'setting.branding.title',
                    layoutProps.locale,
                    layoutProps.fallbackLocale,
                ),
                href: edit(),
            },
        ],
    }),
});

const { t } = useTranslations();

/**
 * Radio cards carry the group's error state themselves, so an invalid choice is
 * visible across the whole option and not only on its radio circle.
 */
const radioCardClass = (hasError: boolean): string =>
    hasError
        ? 'border-destructive ring-3 ring-destructive/20 has-[[data-state=checked]]:border-destructive dark:ring-destructive/40'
        : 'has-[[data-state=checked]]:border-primary';

const authLayout = ref(props.settings.authLayout);
const appLayout = ref(props.settings.appLayout);
const colorTheme = ref(props.settings.colorTheme);
const fontPreset = ref(props.settings.fontPreset);
</script>

<template>
    <div class="contents">
        <Head :title="t('setting.branding.title')" />

        <PageWrapper
            :title="t('setting.branding.title')"
            :description="t('setting.branding.description')"
        >
            <Form
                v-bind="BrandingSettingsController.update.form()"
                :options="{ preserveScroll: true }"
                class="space-y-6"
                v-slot="{ errors, processing, validate, validating }"
            >
                <div class="grid gap-2">
                    <Label for="companyName">
                        {{ t('setting.branding.label.company_name') }}
                    </Label>
                    <Input
                        id="companyName"
                        name="companyName"
                        :default-value="settings.companyName"
                        :aria-invalid="Boolean(errors.companyName)"
                        @change="validate('companyName')"
                        :placeholder="
                            t('setting.branding.placeholder.company_name')
                        "
                    />
                    <InputError :message="errors.companyName" />
                </div>

                <div class="grid gap-2">
                    <Label for="footerText">
                        {{ t('setting.branding.label.footer_text') }}
                    </Label>
                    <Textarea
                        id="footerText"
                        name="footerText"
                        :default-value="settings.footerText ?? ''"
                        :placeholder="
                            t('setting.branding.placeholder.footer_text')
                        "
                        :aria-invalid="Boolean(errors.footerText)"
                        @change="validate('footerText')"
                    />
                    <InputError :message="errors.footerText" />
                </div>

                <fieldset class="grid gap-3">
                    <legend class="text-sm font-medium">
                        {{ t('setting.branding.label.auth_layout_group') }}
                    </legend>
                    <input
                        type="hidden"
                        name="authLayout"
                        :value="authLayout"
                    />
                    <RadioGroup
                        v-model="authLayout"
                        class="sm:grid-cols-3"
                        :aria-invalid="Boolean(errors.authLayout)"
                        @update:model-value="validate('authLayout')"
                    >
                        <Label
                            v-for="option in authLayoutOptions"
                            :key="option.value"
                            :for="`authLayout-${option.value}`"
                            :class="[
                                'flex cursor-pointer flex-col items-start gap-3 rounded-lg border p-3',
                                radioCardClass(Boolean(errors.authLayout)),
                            ]"
                        >
                            <div
                                class="flex h-16 w-full gap-1 rounded-md bg-muted p-2"
                            >
                                <div
                                    v-if="option.value === 'split'"
                                    class="h-full w-1/2 rounded-sm bg-foreground/20"
                                ></div>
                                <div
                                    :class="[
                                        'flex flex-1 flex-col justify-center gap-1',
                                        option.value === 'card'
                                            ? 'rounded-sm border bg-background p-1'
                                            : '',
                                    ]"
                                >
                                    <div
                                        class="h-1.5 w-2/3 rounded-full bg-foreground/30"
                                    ></div>
                                    <div
                                        class="h-1.5 w-full rounded-full bg-foreground/20"
                                    ></div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <RadioGroupItem
                                    :id="`authLayout-${option.value}`"
                                    :value="option.value"
                                    :aria-invalid="Boolean(errors.authLayout)"
                                />
                                <span class="text-sm">{{ option.label }}</span>
                            </div>
                        </Label>
                    </RadioGroup>
                    <InputError :message="errors.authLayout" />
                </fieldset>

                <fieldset class="grid gap-3">
                    <legend class="text-sm font-medium">
                        {{ t('setting.branding.label.app_layout_group') }}
                    </legend>
                    <input type="hidden" name="appLayout" :value="appLayout" />
                    <RadioGroup
                        v-model="appLayout"
                        class="sm:grid-cols-2"
                        :aria-invalid="Boolean(errors.appLayout)"
                        @update:model-value="validate('appLayout')"
                    >
                        <Label
                            v-for="option in appLayoutOptions"
                            :key="option.value"
                            :for="`appLayout-${option.value}`"
                            :class="[
                                'flex cursor-pointer flex-col items-start gap-3 rounded-lg border p-3',
                                radioCardClass(Boolean(errors.appLayout)),
                            ]"
                        >
                            <div
                                :class="[
                                    'flex h-16 w-full gap-1 rounded-md bg-muted p-2',
                                    option.value === 'sidebar'
                                        ? 'flex-row'
                                        : 'flex-col',
                                ]"
                            >
                                <div
                                    :class="[
                                        'rounded-sm bg-foreground/30',
                                        option.value === 'sidebar'
                                            ? 'h-full w-1/4'
                                            : 'h-1/4 w-full',
                                    ]"
                                ></div>
                                <div
                                    class="flex-1 rounded-sm bg-foreground/10"
                                ></div>
                            </div>
                            <div class="flex items-center gap-2">
                                <RadioGroupItem
                                    :id="`appLayout-${option.value}`"
                                    :value="option.value"
                                    :aria-invalid="Boolean(errors.appLayout)"
                                />
                                <span class="text-sm">{{ option.label }}</span>
                            </div>
                        </Label>
                    </RadioGroup>
                    <InputError :message="errors.appLayout" />
                </fieldset>

                <fieldset class="grid gap-3">
                    <legend class="text-sm font-medium">
                        {{ t('setting.branding.label.color_theme_group') }}
                    </legend>
                    <input
                        type="hidden"
                        name="colorTheme"
                        :value="colorTheme"
                    />
                    <RadioGroup
                        v-model="colorTheme"
                        class="sm:grid-cols-3"
                        :aria-invalid="Boolean(errors.colorTheme)"
                        @update:model-value="validate('colorTheme')"
                    >
                        <Label
                            v-for="option in colorThemeOptions"
                            :key="option.value"
                            :for="`colorTheme-${option.value}`"
                            :class="[
                                'flex cursor-pointer items-center gap-2 rounded-lg border p-3',
                                radioCardClass(Boolean(errors.colorTheme)),
                            ]"
                        >
                            <RadioGroupItem
                                :id="`colorTheme-${option.value}`"
                                :value="option.value"
                                :aria-invalid="Boolean(errors.colorTheme)"
                            />
                            <span
                                :data-color-theme="option.value"
                                class="size-4 shrink-0 rounded-full bg-primary"
                                aria-hidden="true"
                            ></span>
                            <span class="text-sm">{{ option.label }}</span>
                        </Label>
                    </RadioGroup>
                    <InputError :message="errors.colorTheme" />
                </fieldset>

                <fieldset class="grid gap-3">
                    <legend class="text-sm font-medium">
                        {{ t('setting.branding.label.font_preset_group') }}
                    </legend>
                    <input
                        type="hidden"
                        name="fontPreset"
                        :value="fontPreset"
                    />
                    <RadioGroup
                        v-model="fontPreset"
                        $!2
                        :aria-invalid="Boolean(errors.fontPreset)"
                        @update:model-value="validate('fontPreset')"
                    >
                        <Label
                            v-for="option in fontPresetOptions"
                            :key="option.value"
                            :for="`fontPreset-${option.value}`"
                            :class="[
                                'flex cursor-pointer items-center gap-3 rounded-lg border p-3',
                                radioCardClass(Boolean(errors.fontPreset)),
                            ]"
                        >
                            <RadioGroupItem
                                :id="`fontPreset-${option.value}`"
                                :value="option.value"
                                :aria-invalid="Boolean(errors.fontPreset)"
                            />
                            <span
                                :data-font-preset="option.value"
                                class="flex flex-col font-sans"
                            >
                                <span class="text-sm">{{ option.label }}</span>
                                <span class="text-sm text-muted-foreground">
                                    {{ t('setting.branding.preview.font') }}
                                </span>
                            </span>
                        </Label>
                    </RadioGroup>
                    <InputError :message="errors.fontPreset" />
                </fieldset>

                <div class="flex items-center gap-4">
                    <Button
                        :disabled="processing || validating"
                        data-test="update-branding-settings-button"
                    >
                        {{ t('setting.branding.button.save') }}
                    </Button>
                </div>
            </Form>
        </PageWrapper>
    </div>
</template>
