<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import GeneralSettingsController from '@/actions/App/Http/Controllers/Settings/GeneralSettingsController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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
import { Textarea } from '@/components/ui/textarea';
import { translate, useTranslations } from '@/composables/useTranslations';
import { edit } from '@/routes/settings/general';
import type { SelectOption } from '@/types';

type GeneralSettings = {
    applicationName: string;
    description: string | null;
    defaultLocale: string;
    dateFormat: string;
};

type LayoutProps = {
    locale: string;
    fallbackLocale: string;
};

const props = defineProps<{
    settings: GeneralSettings;
    localeOptions: SelectOption[];
    dateFormatOptions: SelectOption[];
}>();

defineOptions({
    layout: (layoutProps: LayoutProps) => ({
        breadcrumbs: [
            {
                title: translate(
                    'setting.general.title',
                    layoutProps.locale,
                    layoutProps.fallbackLocale,
                ),
                href: edit(),
            },
        ],
    }),
});

const { t } = useTranslations();

const defaultLocale = ref(props.settings.defaultLocale);
const dateFormat = ref(props.settings.dateFormat);

const optionLabel = (options: SelectOption[], value: string): string =>
    options.find((option) => option.value === value)?.label ?? value;

const localeLabel = computed(() =>
    optionLabel(props.localeOptions, defaultLocale.value),
);
const dateFormatLabel = computed(() =>
    optionLabel(props.dateFormatOptions, dateFormat.value),
);
</script>

<template>
    <Head :title="t('setting.general.title')" />

    <h1 class="sr-only">{{ t('setting.general.title') }}</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            :title="t('setting.general.title')"
            :description="t('setting.general.description')"
        />

        <Form
            v-bind="GeneralSettingsController.update.form()"
            :options="{ preserveScroll: true }"
            class="space-y-6"
            v-slot="{ errors, processing, validate, validating }"
        >
            <div class="grid gap-2">
                <Label for="applicationName">
                    {{ t('setting.general.label.application_name') }}
                </Label>
                <Input
                    id="applicationName"
                    class="mt-1 block w-full"
                    name="applicationName"
                    :default-value="settings.applicationName"
                    required
                    @change="validate('applicationName')"
                    :placeholder="
                        t('setting.general.placeholder.application_name')
                    "
                />
                <p class="text-sm text-muted-foreground">
                    {{ t('setting.general.help.application_name') }}
                </p>
                <InputError class="mt-2" :message="errors.applicationName" />
            </div>

            <div class="grid gap-2">
                <Label for="description">
                    {{ t('setting.general.label.description') }}
                </Label>
                <Textarea
                    id="description"
                    class="mt-1 block w-full"
                    name="description"
                    :default-value="settings.description ?? ''"
                    :placeholder="t('setting.general.placeholder.description')"
                    @change="validate('description')"
                />
                <p class="text-sm text-muted-foreground">
                    {{ t('setting.general.help.description') }}
                </p>
                <InputError class="mt-2" :message="errors.description" />
            </div>

            <div class="grid gap-2">
                <Label for="defaultLocale">
                    {{ t('setting.general.label.default_locale') }}
                </Label>
                <input
                    type="hidden"
                    name="defaultLocale"
                    :value="defaultLocale"
                />
                <Select
                    v-model="defaultLocale"
                    @update:model-value="validate('defaultLocale')"
                >
                    <SelectTrigger id="defaultLocale" class="w-full">
                        <SelectValue>{{ localeLabel }}</SelectValue>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in localeOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <p class="text-sm text-muted-foreground">
                    {{ t('setting.general.help.default_locale') }}
                </p>
                <InputError class="mt-2" :message="errors.defaultLocale" />
            </div>

            <div class="grid gap-2">
                <Label for="dateFormat">
                    {{ t('setting.general.label.date_format') }}
                </Label>
                <input type="hidden" name="dateFormat" :value="dateFormat" />
                <Select
                    v-model="dateFormat"
                    @update:model-value="validate('dateFormat')"
                >
                    <SelectTrigger id="dateFormat" class="w-full">
                        <SelectValue>{{ dateFormatLabel }}</SelectValue>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in dateFormatOptions"
                            :key="option.value"
                            :value="option.value"
                            :data-test="`date-format-option-${option.value}`"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <p class="text-sm text-muted-foreground">
                    {{ t('setting.general.help.date_format') }}
                </p>
                <InputError class="mt-2" :message="errors.dateFormat" />
            </div>

            <div class="flex items-center gap-4">
                <Button
                    :disabled="processing || validating"
                    data-test="update-general-settings-button"
                >
                    {{ t('setting.general.button.save') }}
                </Button>
            </div>
        </Form>
    </div>
</template>
