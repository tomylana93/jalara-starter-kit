import type * as InertiaCore from '@inertiajs/core';
import type * as Inertia from '@inertiajs/vue3';
import { config } from '@vue/test-utils';
import { Primitive } from 'reka-ui';
import { vi } from 'vitest';
import { defineComponent, h, reactive } from 'vue';
import type { NotificationItem } from '@/types/notifications';

export const formState = {
    errors: {} as Record<string, string>,
    processing: false,
    validating: false,
    validate: vi.fn(),
};

/**
 * Records what the standalone `useHttp` helper was asked to send, so a test can
 * assert the request and drive the success path without a real XHR transport.
 */
export const httpState = {
    submissions: [] as { href: unknown; data: Record<string, unknown> }[],
    cancelled: 0,
    response: { data: [] } as unknown,
};

export const resetHttpState = (): void => {
    httpState.submissions = [];
    httpState.cancelled = 0;
    httpState.response = { data: [] };
};

export const inertiaPageProps = {
    auth: {
        user: null as { id?: string; name: string; avatar?: string } | null,
    },
    branding: {},
    errors: {} as Record<string, string>,
    name: undefined as string | undefined,
    description: null as string | null,
    version: '0.1.0',
    can: {
        manageSettings: true,
        manageBackups: true,
        viewUsers: true,
        auditChat: true,
        manageDocumentation: true,
    },
    locale: 'en',
    fallbackLocale: 'en',
    notificationBell: {
        items: [] as NotificationItem[],
        unreadCount: 0,
    },
    chat: {
        enabled: true,
        imageUploadsEnabled: true,
        unreadCount: 0,
    },
};

/**
 * Captures what the Echo notification composable was asked to subscribe to, so
 * a test can assert the channel and drive a broadcast through the real callback.
 */
export const echoState = {
    channel: null as string | null,
    callback: null as ((notification: NotificationItem) => void) | null,
};

export const resetEchoState = (): void => {
    echoState.channel = null;
    echoState.callback = null;
};

export const inertiaPageUrl = {
    value: '/',
};

/**
 * Records what the Inertia XHR client behind `chatClient` and `imageUploads`
 * was asked to send, so a test can assert the request and choose the response.
 */
export const inertiaClientState = {
    requests: [] as InertiaCore.HttpRequestConfig[],
    response: {
        data: {},
        status: 200,
        headers: {},
    } as InertiaCore.HttpResponse,
};

export const resetInertiaClientState = (): void => {
    inertiaClientState.requests = [];
    inertiaClientState.response = {
        data: {},
        status: 200,
        headers: {},
    } as InertiaCore.HttpResponse;
};

/*
 * jsdom has no server to answer the Inertia XHR client, so an unmocked call
 * opened a real socket and reported the refused connection after the run. The
 * stub keeps the client's request/response shape and hands the call to
 * `inertiaClientState`; a test that needs richer behaviour still mocks
 * `@inertiajs/core` or the calling module itself.
 */
vi.mock('@inertiajs/core', async (importOriginal) => {
    const original = await importOriginal<typeof InertiaCore>();
    const http = Object.create(original.http) as typeof original.http;

    http.getClient = () =>
        ({
            request: (config: InertiaCore.HttpRequestConfig) => {
                inertiaClientState.requests.push(config);

                return Promise.resolve(inertiaClientState.response);
            },
        }) as ReturnType<typeof original.http.getClient>;

    return { ...original, http };
});

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
        /*
         * The real component drives Inertia partial reloads through intersection
         * observers, which jsdom has none of. The stub keeps the wrapper element
         * and the slots so a test can still assert what the merged prop renders.
         */
        InfiniteScroll: defineComponent({
            inheritAttrs: false,
            props: {
                data: { type: String, default: '' },
            },
            setup(props, { attrs, slots }) {
                return () =>
                    h(
                        'div',
                        { ...attrs, 'data-infinite-scroll': props.data },
                        slots.default?.({
                            loading: false,
                            loadingPrevious: false,
                            loadingNext: false,
                        }),
                    );
            },
        }),
        Head: defineComponent({
            setup(_, { slots }) {
                return () => h('div', { 'data-head': '' }, slots.default?.());
            },
        }),
        Link: defineComponent({
            inheritAttrs: false,
            setup(_, { attrs, slots }) {
                return () => {
                    /*
                     * The real Link accepts a Wayfinder route definition as well
                     * as a string, so the object is resolved to its url here
                     * instead of stringifying to "[object Object]".
                     */
                    const { href, ...rest } = attrs;
                    const url =
                        typeof href === 'object' &&
                        href !== null &&
                        'url' in href
                            ? (href as { url: string }).url
                            : href;

                    return h('a', { ...rest, href: url }, slots.default?.());
                };
            },
        }),
        /*
         * The real helper drives Inertia's XHR client, which jsdom has no
         * transport for. The stub keeps the reactive data bag, the url/method
         * pair passed to submit, and the cancel call that drops a superseded
         * request.
         */
        useHttp: (initial: Record<string, unknown> = {}) => {
            const keys = Object.keys(initial);
            const http: Record<string, unknown> = reactive({
                ...initial,
                processing: false,
                response: null,
            });

            http.submit = (
                href: unknown,
                options: { onSuccess?: (response: unknown) => void } = {},
            ) => {
                httpState.submissions.push({
                    href,
                    data: Object.fromEntries(
                        keys.map((key) => [key, http[key]]),
                    ),
                });
                options.onSuccess?.(httpState.response);

                return Promise.resolve(httpState.response);
            };
            http.cancel = () => {
                httpState.cancelled += 1;
            };

            return http;
        },
        usePage: () => ({
            props: inertiaPageProps,
            get url() {
                return inertiaPageUrl.value;
            },
        }),
    };
});

/*
 * jsdom has no WebSocket transport, so Echo is replaced by a mock that keeps the
 * real composable's return shape and hands the callback to `echoState`.
 */
vi.mock('@laravel/echo-vue', () => ({
    configureEcho: vi.fn(),
    echo: vi.fn(),
    echoIsConfigured: () => true,
    useEchoNotification: (
        channel: string,
        callback: (notification: NotificationItem) => void,
    ) => {
        echoState.channel = channel;
        echoState.callback = callback;

        return {
            listen: vi.fn(),
            stopListening: vi.fn(),
            leaveChannel: vi.fn(),
            leave: vi.fn(),
            channel: vi.fn(),
        };
    },
}));

vi.mock('@/composables/useTranslations', () => ({
    translate: (key: string) => key,
    useTranslations: () => ({ t: (key: string) => key }),
}));

/*
 * Mirrors the `withApp` registration in `resources/js/app.ts`: registry files
 * such as AttachmentTrigger reference Primitive as a global component, and the
 * test runner never boots the Inertia app that provides it.
 */
config.global.components = {
    Primitive,
};

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
        props: ['modelValue'],
        emits: ['update:modelValue'],
        template:
            '<textarea v-bind="$attrs" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)"></textarea>',
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
        /* The real primitive emits `select` when the item is activated. */
        emits: ['select'],
        inheritAttrs: false,
        template:
            '<div role="menuitem" v-bind="$attrs" @click="$emit(\'select\')"><slot /></div>',
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
    /*
     * The context menu only mounts its content once a real pointer opens it and
     * measures the surface through floating-ui, neither of which jsdom has. The
     * stubs keep the item structure and the `select` event so a test can assert
     * what the menu offers and drive the command behind an entry.
     */
    ContextMenu: { template: '<div><slot /></div>' },
    ContextMenuTrigger: { template: '<div><slot /></div>' },
    ContextMenuContent: { template: '<div><slot /></div>' },
    ContextMenuGroup: { template: '<div><slot /></div>' },
    ContextMenuSub: { template: '<div><slot /></div>' },
    ContextMenuSubContent: { template: '<div><slot /></div>' },
    ContextMenuSubTrigger: {
        inheritAttrs: false,
        template: '<div role="menuitem" v-bind="$attrs"><slot /></div>',
    },
    ContextMenuSeparator: { template: '<hr />' },
    ContextMenuItem: {
        /* The real primitive emits `select` when the item is activated. */
        emits: ['select'],
        inheritAttrs: false,
        template:
            '<div role="menuitem" v-bind="$attrs" @click="$emit(\'select\')"><slot /></div>',
    },
    ContextMenuCheckboxItem: {
        props: ['modelValue'],
        emits: ['select'],
        inheritAttrs: false,
        template:
            '<button type="button" role="menuitemcheckbox" :aria-checked="String(Boolean(modelValue))" v-bind="$attrs" @click="$emit(\'select\')"><slot /></button>',
    },
    ContextMenuRadioGroup: {
        name: 'ContextMenuRadioGroup',
        props: ['modelValue'],
        template:
            '<div role="radiogroup" :data-value="modelValue"><slot /></div>',
    },
    ContextMenuRadioItem: {
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
    /*
     * The message-scroller registry primitives measure real layout through
     * ResizeObserver and MutationObserver, which jsdom does not provide. The
     * stubs keep their structure and the viewport's scroll event so a test can
     * still assert what is rendered and how the surface reacts.
     */
    MessageScrollerProvider: { template: '<div><slot /></div>' },
    MessageScroller: {
        inheritAttrs: false,
        template: '<div v-bind="$attrs"><slot /></div>',
    },
    MessageScrollerViewport: {
        inheritAttrs: false,
        template: '<div v-bind="$attrs"><slot /></div>',
    },
    MessageScrollerContent: { template: '<div><slot /></div>' },
    MessageScrollerItem: {
        props: ['messageId'],
        inheritAttrs: false,
        template:
            '<div v-bind="$attrs" :data-message-id="messageId"><slot /></div>',
    },
    MessageScrollerButton: {
        inheritAttrs: false,
        template: '<button type="button" v-bind="$attrs"><slot /></button>',
    },
    Message: {
        props: ['align'],
        template: '<div :data-align="align"><slot /></div>',
    },
    MessageContent: { template: '<div><slot /></div>' },
    MessageHeader: { template: '<div><slot /></div>' },
    Bubble: {
        props: ['align', 'variant'],
        template: '<div :data-variant="variant"><slot /></div>',
    },
    BubbleContent: {
        inheritAttrs: false,
        template: '<div v-bind="$attrs"><slot /></div>',
    },
    Avatar: { template: '<span><slot /></span>' },
    AvatarImage: {
        inheritAttrs: false,
        template: '<img v-bind="$attrs" />',
    },
    AvatarFallback: { template: '<span><slot /></span>' },
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
