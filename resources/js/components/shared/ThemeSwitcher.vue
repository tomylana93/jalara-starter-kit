<script setup lang="ts">
import type { AcceptableValue } from 'reka-ui';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { Appearance } from '@/composables/useAppearance';
import { useAppearance } from '@/composables/useAppearance';
import { t } from '@/lib/i18n';
import { themeOptions } from '@/lib/appearance';

const { appearance, updateAppearance } = useAppearance();
const activeOption = computed(
    () =>
        themeOptions.find((option) => option.value === appearance.value) ??
        themeOptions[0],
);

const selectAppearance = (value: AcceptableValue): void => {
    if (
        typeof value !== 'string' ||
        !themeOptions.some((option) => option.value === value)
    ) {
        return;
    }

    updateAppearance(value as Appearance);
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                :aria-label="t('appearance.label.selector')"
            >
                <component :is="activeOption.icon" data-icon="inline-start" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuRadioGroup
                :model-value="appearance"
                @update:model-value="selectAppearance"
            >
                <DropdownMenuRadioItem
                    v-for="option in themeOptions"
                    :key="option.value"
                    :value="option.value"
                >
                    <component :is="option.icon" />
                    {{ t(option.labelKey) }}
                </DropdownMenuRadioItem>
            </DropdownMenuRadioGroup>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
