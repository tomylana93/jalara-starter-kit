<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import GeneralSettingsController from '@/actions/App/Http/Controllers/Settings/GeneralSettingsController';
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
import { Textarea } from '@/components/ui/textarea';
import { useTranslations } from '@/composables/useTranslations';
import { breadcrumbLayout } from '@/lib/breadcrumbs';
import { index as settingsIndex } from '@/routes/settings';
import { edit } from '@/routes/settings/general';
import type { SelectOption } from '@/types';

type GeneralSettings = {
    applicationName: string;
    description: string | null;
    defaultLocale: string;
    dateFormat: string;
};

const props = defineProps<{
    settings: GeneralSettings;
    localeOptions: SelectOption[];
    dateFormatOptions: SelectOption[];
}>();

defineOptions({
    layout: breadcrumbLayout(() => [
        { key: 'setting.layout.title', href: settingsIndex() },
        { key: 'setting.general.title', href: edit() },
    ]),
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
    <div class="contents">
        <Head :title="t('setting.general.title')" />

        <PageWrapper
            :title="t('setting.general.title')"
            :description="t('setting.general.description')"
        >
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
                    <p class="text-sm text-muted-foreground">
                        {{ t('setting.general.help.application_name') }}
                    </p>
                    <Input
                        id="applicationName"
                        name="applicationName"
                        :default-value="settings.applicationName"
                        :aria-invalid="Boolean(errors.applicationName)"
                        @change="validate('applicationName')"
                        :placeholder="
                            t('setting.general.placeholder.application_name')
                        "
                    />
                    <InputError :message="errors.applicationName" />
                </div>

                <div class="grid gap-2">
                    <Label for="description">
                        {{ t('setting.general.label.description') }}
                    </Label>
                    <p class="text-sm text-muted-foreground">
                        {{ t('setting.general.help.description') }}
                    </p>
                    <Textarea
                        id="description"
                        name="description"
                        :default-value="settings.description ?? ''"
                        :placeholder="
                            t('setting.general.placeholder.description')
                        "
                        :aria-invalid="Boolean(errors.description)"
                        @change="validate('description')"
                    />
                    <InputError :message="errors.description" />
                </div>

                <div class="grid gap-2">
                    <Label for="defaultLocale">
                        {{ t('setting.general.label.default_locale') }}
                    </Label>
                    <p class="text-sm text-muted-foreground">
                        {{ t('setting.general.help.default_locale') }}
                    </p>
                    <input
                        type="hidden"
                        name="defaultLocale"
                        :value="defaultLocale"
                    />
                    <Select
                        v-model="defaultLocale"
                        @update:model-value="validate('defaultLocale')"
                    >
                        <SelectTrigger
                            id="defaultLocale"
                            class="w-full"
                            :aria-invalid="Boolean(errors.defaultLocale)"
                        >
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
                    <InputError :message="errors.defaultLocale" />
                </div>

                <div class="grid gap-2">
                    <Label for="dateFormat">
                        {{ t('setting.general.label.date_format') }}
                    </Label>
                    <p class="text-sm text-muted-foreground">
                        {{ t('setting.general.help.date_format') }}
                    </p>
                    <input
                        type="hidden"
                        name="dateFormat"
                        :value="dateFormat"
                    />
                    <Select
                        v-model="dateFormat"
                        @update:model-value="validate('dateFormat')"
                    >
                        <SelectTrigger
                            id="dateFormat"
                            class="w-full"
                            :aria-invalid="Boolean(errors.dateFormat)"
                        >
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
                    <InputError :message="errors.dateFormat" />
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
        </PageWrapper>
    </div>
</template>
