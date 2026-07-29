<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import BrandingSettingsController from '@/actions/App/Http/Controllers/Settings/BrandingSettingsController';
import ImageUploadField from '@/components/ImageUploadField.vue';
import InputError from '@/components/InputError.vue';
import PageWrapper from '@/components/PageWrapper.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Textarea } from '@/components/ui/textarea';
import UploadGuardOverlay from '@/components/UploadGuardOverlay.vue';
import { useBranding } from '@/composables/useBranding';
import { translate, useTranslations } from '@/composables/useTranslations';
import { index as settingsIndex } from '@/routes/settings';
import { edit } from '@/routes/settings/branding';
import {
    destroy as assetDestroy,
    store as assetStore,
} from '@/routes/settings/branding/asset';
import type { SelectOption } from '@/types';

type BrandingSettings = {
    companyName: string;
    footerText: string | null;
    identityMode: string;
    authLayout: string;
    appLayout: string;
    colorTheme: string;
    fontPair: string;
};

type LayoutProps = {
    locale: string;
    fallbackLocale: string;
};

const props = defineProps<{
    settings: BrandingSettings;
    identityModeOptions: SelectOption[];
    authLayoutOptions: SelectOption[];
    appLayoutOptions: SelectOption[];
    colorThemeOptions: SelectOption[];
    fontPairOptions: SelectOption[];
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

/*
 * The shared branding prop carries the stored image URLs, so the previews stay
 * in step with every other branded surface after an upload.
 */
const { branding } = useBranding();

/**
 * Radio cards carry the group's error state themselves, so an invalid choice is
 * visible across the whole option and not only on its radio circle.
 */
const radioCardClass = (hasError: boolean): string =>
    hasError
        ? 'border-destructive ring-3 ring-destructive/20 has-[[data-state=checked]]:border-destructive dark:ring-destructive/40'
        : 'has-[[data-state=checked]]:border-primary';

const identityMode = ref(props.settings.identityMode);
const authLayout = ref(props.settings.authLayout);
const appLayout = ref(props.settings.appLayout);
const colorTheme = ref(props.settings.colorTheme);
const fontPair = ref(props.settings.fontPair);
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
                        {{ t('setting.branding.label.identity_mode_group') }}
                    </legend>
                    <p class="text-sm text-muted-foreground">
                        {{ t('setting.branding.help.identity_mode_group') }}
                    </p>
                    <input
                        type="hidden"
                        name="identityMode"
                        :value="identityMode"
                    />
                    <RadioGroup
                        v-model="identityMode"
                        class="sm:grid-cols-2"
                        :aria-invalid="Boolean(errors.identityMode)"
                        @update:model-value="validate('identityMode')"
                    >
                        <Label
                            v-for="option in identityModeOptions"
                            :key="option.value"
                            :for="`identityMode-${option.value}`"
                            :class="[
                                'flex cursor-pointer flex-col items-start gap-3 rounded-lg border p-3',
                                radioCardClass(Boolean(errors.identityMode)),
                            ]"
                        >
                            <!--
                                The sketch shows what a branded surface renders:
                                the wide logo on its own, or the square icon
                                followed by the application name.
                            -->
                            <div
                                :data-color-theme="colorTheme"
                                data-test="identity-preview"
                                class="flex h-16 w-full items-center justify-center gap-2 rounded-md bg-muted p-2"
                            >
                                <template v-if="option.value === 'logo'">
                                    <div
                                        class="h-5 w-24 rounded-sm bg-primary"
                                    ></div>
                                </template>
                                <template v-else>
                                    <div
                                        class="size-5 shrink-0 rounded-sm bg-primary"
                                    ></div>
                                    <div
                                        class="h-2 w-16 rounded-full bg-accent-foreground/40"
                                    ></div>
                                </template>
                            </div>
                            <div class="flex items-center gap-2">
                                <RadioGroupItem
                                    :id="`identityMode-${option.value}`"
                                    :value="option.value"
                                    :aria-invalid="Boolean(errors.identityMode)"
                                />
                                <span class="text-sm">{{ option.label }}</span>
                            </div>
                        </Label>
                    </RadioGroup>
                    <InputError :message="errors.identityMode" />
                </fieldset>

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
                            <!--
                                Each sketch mirrors the matching auth layout:
                                the page background, where the brand mark sits,
                                and whether the form is wrapped in a card.
                            -->
                            <div
                                v-if="option.value === 'split'"
                                :data-color-theme="colorTheme"
                                data-test="auth-preview"
                                class="flex h-16 w-full gap-1 rounded-md bg-primary p-2"
                            >
                                <!--
                                    The split layout puts the brand mark in the
                                    top-left of the image panel, not above the
                                    form like the other two layouts.
                                -->
                                <div
                                    class="h-full w-1/2 rounded-sm bg-primary-foreground/20 p-1"
                                >
                                    <div
                                        class="size-1.5 rounded-full bg-primary-foreground/80"
                                    ></div>
                                </div>
                                <div
                                    class="flex flex-1 flex-col items-center justify-center gap-1"
                                >
                                    <div
                                        class="h-1.5 w-2/3 rounded-full bg-primary-foreground/50"
                                    ></div>
                                    <div
                                        class="h-1.5 w-full rounded-full bg-primary-foreground/30"
                                    ></div>
                                </div>
                            </div>
                            <div
                                v-else-if="option.value === 'card'"
                                :data-color-theme="colorTheme"
                                data-test="auth-preview"
                                class="flex h-16 w-full flex-col items-center justify-center gap-1 rounded-md bg-muted p-1.5"
                            >
                                <div
                                    class="size-1.5 shrink-0 rounded-full bg-primary"
                                ></div>
                                <div
                                    class="flex w-full flex-1 flex-col items-center justify-center gap-1 rounded-md border bg-background px-2 shadow-sm"
                                >
                                    <div
                                        class="h-1.5 w-1/2 rounded-full bg-primary"
                                    ></div>
                                    <div
                                        class="h-1.5 w-3/4 rounded-full bg-accent"
                                    ></div>
                                </div>
                            </div>
                            <div
                                v-else
                                :data-color-theme="colorTheme"
                                data-test="auth-preview"
                                class="flex h-16 w-full flex-col items-center justify-center gap-1 rounded-md border bg-background p-2"
                            >
                                <div
                                    class="size-2 shrink-0 rounded-full bg-primary"
                                ></div>
                                <div
                                    class="h-1.5 w-1/2 rounded-full bg-primary"
                                ></div>
                                <div
                                    class="h-1.5 w-3/4 rounded-full bg-accent"
                                ></div>
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
                                :data-color-theme="colorTheme"
                                data-test="app-preview"
                                :class="[
                                    'flex h-16 w-full gap-1 rounded-md bg-muted p-2',
                                    option.value === 'sidebar'
                                        ? 'flex-row'
                                        : 'flex-col',
                                ]"
                            >
                                <div
                                    :class="[
                                        'rounded-sm bg-primary',
                                        option.value === 'sidebar'
                                            ? 'h-full w-1/4'
                                            : 'h-1/4 w-full',
                                    ]"
                                ></div>
                                <div
                                    class="flex-1 rounded-sm border bg-card"
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
                                class="flex shrink-0 overflow-hidden rounded-full border border-border"
                                aria-hidden="true"
                            >
                                <span class="size-4 bg-primary"></span>
                                <span class="size-4 bg-accent"></span>
                                <span class="size-4 bg-background"></span>
                            </span>
                            <span class="text-sm">{{ option.label }}</span>
                        </Label>
                    </RadioGroup>
                    <InputError :message="errors.colorTheme" />
                </fieldset>

                <fieldset class="grid gap-3">
                    <legend class="text-sm font-medium">
                        {{ t('setting.branding.label.font_pair_group') }}
                    </legend>
                    <input type="hidden" name="fontPair" :value="fontPair" />
                    <RadioGroup
                        v-model="fontPair"
                        class="sm:grid-cols-2"
                        :aria-invalid="Boolean(errors.fontPair)"
                        @update:model-value="validate('fontPair')"
                    >
                        <Label
                            v-for="option in fontPairOptions"
                            :key="option.value"
                            :for="`fontPair-${option.value}`"
                            :class="[
                                'flex cursor-pointer items-center gap-3 rounded-lg border p-3',
                                radioCardClass(Boolean(errors.fontPair)),
                            ]"
                        >
                            <RadioGroupItem
                                :id="`fontPair-${option.value}`"
                                :value="option.value"
                                :aria-invalid="Boolean(errors.fontPair)"
                            />
                            <span
                                :data-font-pair="option.value"
                                class="flex flex-col font-sans"
                            >
                                <span
                                    class="font-heading text-base font-semibold"
                                >
                                    {{ option.label }}
                                </span>
                                <span class="text-sm text-muted-foreground">
                                    {{
                                        t('setting.branding.preview.font_body')
                                    }}
                                </span>
                            </span>
                        </Label>
                    </RadioGroup>
                    <InputError :message="errors.fontPair" />
                </fieldset>

                <!--
                    The fields sit in the settings form for layout, but each
                    posts to its own endpoint: the inputs carry no name, so a
                    pending file never travels with a save.
                -->
                <div class="grid gap-6 lg:grid-cols-2">
                    <ImageUploadField
                        :label="t('setting.branding.label.logo')"
                        :current-url="branding.logoUrl"
                        :upload-url="assetStore('logo').url"
                        test-id="branding-logo"
                        :delete-url="assetDestroy('logo').url"
                        shape="wide"
                        :ratio="3"
                    />

                    <ImageUploadField
                        :label="t('setting.branding.label.logo_dark')"
                        :current-url="branding.logoDarkUrl"
                        :upload-url="assetStore('logo-dark').url"
                        test-id="branding-logo-dark"
                        :delete-url="assetDestroy('logo-dark').url"
                        shape="wide"
                        :ratio="3"
                    />

                    <ImageUploadField
                        :label="t('setting.branding.label.icon')"
                        :current-url="branding.iconUrl"
                        :upload-url="assetStore('icon').url"
                        test-id="branding-icon"
                        :delete-url="assetDestroy('icon').url"
                        shape="wide"
                        :ratio="3"
                    />

                    <ImageUploadField
                        :label="t('setting.branding.label.icon_dark')"
                        :current-url="branding.iconDarkUrl"
                        :upload-url="assetStore('icon-dark').url"
                        test-id="branding-icon-dark"
                        :delete-url="assetDestroy('icon-dark').url"
                        shape="wide"
                        :ratio="3"
                    />

                    <!--
                        Always visible: the label carries the fact that only the
                        split layout uses it, so the field never disappears with
                        a layout change and the stored image stays reachable.
                    -->
                    <ImageUploadField
                        class="lg:col-span-2"
                        :label="t('setting.branding.label.auth_background')"
                        :current-url="branding.authBackgroundUrl"
                        :upload-url="assetStore('auth-background').url"
                        test-id="branding-auth-background"
                        :delete-url="assetDestroy('auth-background').url"
                        shape="wide"
                        :ratio="16 / 9"
                    />
                </div>

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

        <UploadGuardOverlay />
    </div>
</template>
