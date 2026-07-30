<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button, buttonVariants } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslations } from '@/composables/useTranslations';
import type { SelectOption } from '@/types';
import type { RouteDefinition, RouteFormDefinition } from '@/wayfinder';

type UserFormValues = {
    name: string;
    email: string;
    role: string | null;
    status?: string;
};

const props = defineProps<{
    action: RouteFormDefinition<'post'> | RouteFormDefinition<'put'>;
    roleOptions: SelectOption[];
    cancelHref: RouteDefinition<'get'>;
    submitLabel: string;
    user?: UserFormValues;
    /*
     * Only supplied by the edit screen. Creation has no status contract at all,
     * so leaving this undefined keeps every status control out of the DOM.
     */
    statusOptions?: SelectOption[];
}>();

const { t } = useTranslations();

const role = ref(props.user?.role ?? '');
const status = ref(props.user?.status ?? '');

const optionLabel = (
    options: SelectOption[],
    value: string,
    placeholder: string,
): string =>
    options.find((option) => option.value === value)?.label ?? placeholder;

const roleLabel = computed(() =>
    optionLabel(
        props.roleOptions,
        role.value,
        t('master_data.user.placeholder.role'),
    ),
);

const statusLabel = computed(() =>
    optionLabel(
        props.statusOptions ?? [],
        status.value,
        t('master_data.user.placeholder.status'),
    ),
);
</script>

<template>
    <Form
        v-bind="props.action"
        :options="{ preserveScroll: true }"
        class="space-y-6"
        v-slot="{ errors, processing, validate, validating }"
    >
        <div class="grid gap-2">
            <Label for="name">{{ t('master_data.user.label.name') }}</Label>
            <Input
                id="name"
                name="name"
                :default-value="props.user?.name ?? ''"
                :placeholder="t('master_data.user.placeholder.name')"
                :aria-invalid="Boolean(errors.name)"
                @change="validate('name')"
            />
            <InputError :message="errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="email">{{ t('master_data.user.label.email') }}</Label>
            <Input
                id="email"
                name="email"
                type="email"
                inputmode="email"
                :default-value="props.user?.email ?? ''"
                :placeholder="t('master_data.user.placeholder.email')"
                :aria-invalid="Boolean(errors.email)"
                @change="validate('email')"
            />
            <InputError :message="errors.email" />
        </div>

        <div class="grid gap-2">
            <Label for="role">{{ t('master_data.user.label.role') }}</Label>
            <input type="hidden" name="role" :value="role" />
            <Select v-model="role" @update:model-value="validate('role')">
                <SelectTrigger
                    id="role"
                    class="w-full"
                    :aria-invalid="Boolean(errors.role)"
                >
                    <SelectValue>{{ roleLabel }}</SelectValue>
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="option in props.roleOptions"
                        :key="option.value"
                        :value="option.value"
                        :data-test="`role-option-${option.value}`"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="errors.role" />
        </div>

        <div v-if="props.statusOptions" class="grid gap-2">
            <Label for="status">{{ t('master_data.user.label.status') }}</Label>
            <input type="hidden" name="status" :value="status" />
            <Select v-model="status" @update:model-value="validate('status')">
                <SelectTrigger
                    id="status"
                    class="w-full"
                    :aria-invalid="Boolean(errors.status)"
                >
                    <SelectValue>{{ statusLabel }}</SelectValue>
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="option in props.statusOptions"
                        :key="option.value"
                        :value="option.value"
                        :data-test="`status-option-${option.value}`"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="errors.status" />
        </div>

        <div class="flex items-center gap-4">
            <Button
                :disabled="processing || validating"
                data-test="save-user-button"
            >
                {{ props.submitLabel }}
            </Button>
            <Link
                :href="props.cancelHref"
                :class="buttonVariants({ variant: 'ghost' })"
            >
                {{ t('master_data.user.button.cancel') }}
            </Link>
        </div>
    </Form>
</template>
