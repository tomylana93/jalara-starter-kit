<script setup lang="ts">
import { Eye, EyeOff } from '@lucide/vue';
import { ref, useTemplateRef } from 'vue';
import type { HTMLAttributes } from 'vue';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupButton,
    InputGroupInput,
} from '@/components/ui/input-group';
import { useTranslations } from '@/composables/useTranslations';

defineOptions({ inheritAttrs: false });

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();

const showPassword = ref(false);
const inputRef = useTemplateRef('inputRef');
const { t } = useTranslations();

defineExpose({
    $el: inputRef,
    focus: () => inputRef.value?.$el?.focus(),
});
</script>

<template>
    <InputGroup :class="props.class">
        <InputGroupInput
            ref="inputRef"
            :type="showPassword ? 'text' : 'password'"
            v-bind="$attrs"
        />
        <InputGroupAddon align="inline-end">
            <InputGroupButton
                type="button"
                size="icon-xs"
                :aria-label="
                    showPassword
                        ? t('common.password.button.hide')
                        : t('common.password.button.show')
                "
                :tabindex="-1"
                @click="showPassword = !showPassword"
            >
                <EyeOff v-if="showPassword" class="size-4" />
                <Eye v-else class="size-4" />
            </InputGroupButton>
        </InputGroupAddon>
    </InputGroup>
</template>
