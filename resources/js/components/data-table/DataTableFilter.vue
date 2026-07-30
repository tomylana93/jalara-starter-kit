<script setup lang="ts">
import { ListFilter } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTranslations } from '@/composables/useTranslations';
import type { TableFilterOption } from './types';

const props = defineProps<{
    /* The key the server validates; used for identity, never for display. */
    filterKey: string;
    label: string;
    options: TableFilterOption[];
    selected: string[];
}>();

const emit = defineEmits<{
    'update:selected': [values: string[]];
}>();

const { t } = useTranslations();

const isSelected = (value: string): boolean => props.selected.includes(value);

/* Selection order does not matter to the server, so the option order wins. */
const toggle = (value: string): void =>
    emit(
        'update:selected',
        props.options
            .map((option) => option.value)
            .filter((candidate) =>
                candidate === value
                    ? !isSelected(candidate)
                    : isSelected(candidate),
            ),
    );

const selectedCount = computed(() => props.selected.length);
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                variant="outline"
                :data-test="`table-filter-${props.filterKey}`"
            >
                <ListFilter class="mr-2 size-4" />
                {{ props.label }}
                <Badge
                    v-if="selectedCount > 0"
                    variant="secondary"
                    class="ml-2"
                    :data-test="`table-filter-${props.filterKey}-count`"
                >
                    {{ selectedCount }}
                </Badge>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="start">
            <DropdownMenuCheckboxItem
                v-for="option in props.options"
                :key="option.value"
                :model-value="isSelected(option.value)"
                :data-test="`table-filter-${props.filterKey}-${option.value}`"
                @select="(event: Event) => event.preventDefault()"
                @update:model-value="() => toggle(option.value)"
            >
                {{ option.label }}
            </DropdownMenuCheckboxItem>

            <template v-if="selectedCount > 0">
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    :data-test="`table-filter-${props.filterKey}-clear`"
                    @click="emit('update:selected', [])"
                >
                    {{ t('common.table.filter.clear') }}
                </DropdownMenuItem>
            </template>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
