<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldLabel,
} from '@/components/ui/field';
import { Switch } from '@/components/ui/switch';

/*
 * A boolean settings row: label, optional help text, and a switch.
 *
 * The hidden input is what actually submits. A Switch renders a button, which
 * no form serializes, so the value has to travel as a string beside it — and
 * both have to agree, which is why they share one model here rather than being
 * wired up again on every page.
 */

const model = defineModel<boolean>({ required: true });

defineProps<{
    /** Submitted field name; also the id the label points at. */
    name: string;
    label: string;
    description?: string;
    error?: string;
    disabled?: boolean;
    dataTest?: string;
}>();

const emit = defineEmits<{ validate: [] }>();
</script>

<template>
    <!--
        A real box, not `display: contents`. A contents wrapper is skipped by
        the box tree, so a parent's `space-y-*` selects it but its margin never
        renders and the field collides with whatever follows. Vitest cannot see
        that; only a rendered page can.
    -->
    <div>
        <input type="hidden" :name="name" :value="model ? '1' : '0'" />

        <Field orientation="horizontal" :data-disabled="disabled">
            <FieldContent>
                <FieldLabel :for="name">{{ label }}</FieldLabel>
                <FieldDescription v-if="description">
                    {{ description }}
                </FieldDescription>
                <InputError :message="error" />
            </FieldContent>
            <Switch
                :id="name"
                v-model="model"
                :data-test="dataTest"
                :disabled="disabled"
                :aria-invalid="Boolean(error)"
                class="aria-invalid:border-destructive aria-invalid:ring-3 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40"
                @update:model-value="emit('validate')"
            />
        </Field>
    </div>
</template>
