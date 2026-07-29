<script setup lang="ts">
import { Monitor, Moon, Sun } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useAppearance } from '@/composables/useAppearance';
import { useTranslations } from '@/composables/useTranslations';
import type { Appearance } from '@/types';

const { appearance, resolvedAppearance, updateAppearance } = useAppearance();
const { t } = useTranslations();

const options = computed(() => [
    {
        value: 'light' as Appearance,
        Icon: Sun,
        label: t('account.appearance.label.light'),
    },
    {
        value: 'dark' as Appearance,
        Icon: Moon,
        label: t('account.appearance.label.dark'),
    },
    {
        value: 'system' as Appearance,
        Icon: Monitor,
        label: t('account.appearance.label.system'),
    },
]);

const TriggerIcon = computed(() =>
    resolvedAppearance.value === 'dark' ? Moon : Sun,
);
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger :as-child="true">
            <Button
                variant="ghost"
                size="icon"
                type="button"
                class="size-9 cursor-pointer"
                data-test="appearance-toggle"
                :data-appearance="resolvedAppearance"
                :aria-label="t('account.appearance.button.toggle')"
            >
                <component :is="TriggerIcon" class="size-5 opacity-80" />
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-40">
            <DropdownMenuRadioGroup :model-value="appearance">
                <DropdownMenuRadioItem
                    v-for="{ value, Icon, label } in options"
                    :key="value"
                    :value="value"
                    :data-test="`appearance-option-${value}`"
                    @select="updateAppearance(value)"
                >
                    <component :is="Icon" class="size-4" />
                    {{ label }}
                </DropdownMenuRadioItem>
            </DropdownMenuRadioGroup>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
