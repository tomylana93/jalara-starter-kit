import type * as Inertia from '@inertiajs/vue3';
import { config } from '@vue/test-utils';
import { vi } from 'vitest';
import { defineComponent, h } from 'vue';

export const formState = {
    errors: {} as Record<string, string>,
    processing: false,
    validating: false,
    validate: vi.fn(),
};

export const inertiaPageProps = {
    auth: { user: null as { name: string; avatar?: string } | null },
    branding: {},
    name: undefined as string | undefined,
    description: null as string | null,
    can: { manageSettings: true, viewUsers: true },
    locale: 'en',
    fallbackLocale: 'en',
};

export const inertiaPageUrl = {
    value: '/',
};

export const resetFormState = (): void => {
    formState.errors = {};
    formState.processing = false;
    formState.validating = false;
    formState.validate.mockReset();
};

const TestForm = defineComponent({
    name: 'TestForm',
    inheritAttrs: false,
    setup(_, { attrs, slots }) {
        return () =>
            h(
                'form',
                attrs,
                slots.default?.({
                    ...formState,
                }),
            );
    },
});

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const original = await importOriginal<typeof Inertia>();

    return {
        ...original,
        Form: TestForm,
        Head: defineComponent({
            setup(_, { slots }) {
                return () => h('div', { 'data-head': '' }, slots.default?.());
            },
        }),
        Link: defineComponent({
            inheritAttrs: false,
            setup(_, { attrs, slots }) {
                return () => h('a', attrs, slots.default?.());
            },
        }),
        usePage: () => ({
            props: inertiaPageProps,
            get url() {
                return inertiaPageUrl.value;
            },
        }),
    };
});

vi.mock('@/composables/useTranslations', () => ({
    translate: (key: string) => key,
    useTranslations: () => ({ t: (key: string) => key }),
}));

config.global.stubs = {
    PageWrapper: {
        template: '<main><slot name="actions" /><slot /></main>',
    },
    Input: {
        inheritAttrs: false,
        props: ['modelValue'],
        emits: ['update:modelValue'],
        template:
            '<input v-bind="$attrs" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
    },
    Textarea: {
        inheritAttrs: false,
        template: '<textarea v-bind="$attrs"></textarea>',
    },
    PasswordInput: {
        inheritAttrs: false,
        template: '<input type="password" v-bind="$attrs" />',
    },
    Button: {
        inheritAttrs: false,
        template: '<button v-bind="$attrs"><slot /></button>',
    },
    InputError: {
        props: ['message'],
        template: '<p v-if="message">{{ message }}</p>',
    },
    Label: { template: '<label><slot /></label>' },
    Select: { template: '<div><slot /></div>' },
    SelectTrigger: {
        inheritAttrs: false,
        template: '<button type="button" v-bind="$attrs"><slot /></button>',
    },
    SelectValue: { template: '<span><slot /></span>' },
    SelectContent: { template: '<div><slot /></div>' },
    SelectItem: { template: '<button type="button"><slot /></button>' },
    Checkbox: {
        props: ['modelValue'],
        emits: ['update:modelValue'],
        inheritAttrs: false,
        template:
            '<button type="button" role="checkbox" :aria-checked="modelValue === \'indeterminate\' ? \'mixed\' : String(Boolean(modelValue))" v-bind="$attrs" @click="$emit(\'update:modelValue\', modelValue !== true)" />',
    },
    Switch: {
        props: ['modelValue'],
        emits: ['update:modelValue'],
        inheritAttrs: false,
        template:
            '<button type="button" role="switch" v-bind="$attrs" @click="$emit(\'update:modelValue\', !modelValue)" />',
    },
    RadioGroup: {
        name: 'RadioGroup',
        props: ['modelValue'],
        emits: ['update:modelValue'],
        template: '<div role="radiogroup"><slot /></div>',
    },
    RadioGroupItem: {
        inheritAttrs: false,
        template: '<button type="button" role="radio" v-bind="$attrs" />',
    },
    DropdownMenu: { template: '<div><slot /></div>' },
    DropdownMenuTrigger: { template: '<div><slot /></div>' },
    DropdownMenuContent: { template: '<div><slot /></div>' },
    DropdownMenuGroup: { template: '<div><slot /></div>' },
    DropdownMenuLabel: { template: '<div><slot /></div>' },
    DropdownMenuSeparator: { template: '<hr />' },
    DropdownMenuItem: {
        inheritAttrs: false,
        template: '<div role="menuitem" v-bind="$attrs"><slot /></div>',
    },
    DropdownMenuCheckboxItem: {
        props: ['modelValue'],
        emits: ['update:modelValue'],
        inheritAttrs: false,
        template:
            '<button type="button" role="menuitemcheckbox" :aria-checked="String(Boolean(modelValue))" v-bind="$attrs" @click="$emit(\'update:modelValue\', !modelValue)"><slot /></button>',
    },
    DropdownMenuRadioGroup: {
        name: 'DropdownMenuRadioGroup',
        props: ['modelValue'],
        template:
            '<div role="radiogroup" :data-value="modelValue"><slot /></div>',
    },
    DropdownMenuRadioItem: {
        props: ['value'],
        emits: ['select'],
        inheritAttrs: false,
        template:
            '<button type="button" role="menuitemradio" v-bind="$attrs" @click="$emit(\'select\')"><slot /></button>',
    },
    Separator: true,
    Badge: { template: '<span><slot /></span>' },
    AlertDialog: { template: '<div><slot /></div>' },
    AlertDialogTrigger: { template: '<div><slot /></div>' },
    AlertDialogContent: { template: '<section><slot /></section>' },
    AlertDialogHeader: { template: '<header><slot /></header>' },
    AlertDialogTitle: { template: '<h2><slot /></h2>' },
    AlertDialogDescription: { template: '<p><slot /></p>' },
    AlertDialogFooter: { template: '<footer><slot /></footer>' },
    AlertDialogCancel: { template: '<button><slot /></button>' },
    AlertDialogAction: { template: '<button><slot /></button>' },
    Sidebar: { template: '<aside><slot /></aside>' },
    SidebarHeader: { template: '<header><slot /></header>' },
    SidebarContent: { template: '<div><slot /></div>' },
    SidebarFooter: { template: '<footer><slot /></footer>' },
    SidebarMenu: { template: '<div><slot /></div>' },
    SidebarMenuItem: { template: '<div><slot /></div>' },
    SidebarMenuButton: { template: '<div><slot /></div>' },
    SidebarTrigger: { template: '<button type="button" />' },
    Sheet: { template: '<div><slot /></div>' },
    SheetContent: { template: '<div><slot /></div>' },
    SheetHeader: { template: '<header><slot /></header>' },
    SheetTitle: { template: '<h2><slot /></h2>' },
    SheetDescription: { template: '<p><slot /></p>' },
    SheetTrigger: { template: '<div><slot /></div>' },
    NavMain: {
        name: 'NavMain',
        props: ['group', 'items'],
        template: '<nav />',
    },
    NavFooter: true,
    NavUser: true,
    AppLogo: true,
};

Object.defineProperty(window, 'matchMedia', {
    writable: true,
    value: vi.fn().mockImplementation((query: string) => ({
        matches: false,
        media: query,
        onchange: null,
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
        dispatchEvent: vi.fn(),
    })),
});
