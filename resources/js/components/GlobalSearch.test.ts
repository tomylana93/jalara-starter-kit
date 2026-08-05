import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent } from 'vue';
import { httpState, resetHttpState } from '@/test/setup';
import GlobalSearch from './GlobalSearch.vue';

const CommandDialogStub = defineComponent({
    props: {
        open: { type: Boolean, default: false },
    },
    template:
        '<div v-if="open" data-test="global-search-dialog"><slot /></div>',
});

const CommandInputStub = defineComponent({
    props: {
        modelValue: { type: String, default: '' },
    },
    emits: ['update:modelValue'],
    template:
        '<input data-test="global-search-input" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
});

const CommandGroupStub = defineComponent({
    props: {
        heading: { type: String, default: '' },
    },
    template: '<div><p>{{ heading }}</p><slot /></div>',
});

const passthroughStub = { template: '<div><slot /></div>' };

describe('GlobalSearch', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        resetHttpState();
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.unstubAllGlobals();
    });

    function mountSearch() {
        return mount(GlobalSearch, {
            global: {
                stubs: {
                    CommandDialog: CommandDialogStub,
                    CommandInput: CommandInputStub,
                    CommandList: passthroughStub,
                    CommandEmpty: passthroughStub,
                    CommandGroup: CommandGroupStub,
                    CommandItem: passthroughStub,
                    CommandSeparator: passthroughStub,
                    ScrollArea: passthroughStub,
                },
            },
        });
    }

    async function openPalette(wrapper: ReturnType<typeof mountSearch>) {
        window.dispatchEvent(
            new KeyboardEvent('keydown', { key: 'k', ctrlKey: true }),
        );
        await wrapper.vm.$nextTick();

        return wrapper.get('[data-test="global-search-input"]');
    }

    it('opens with Ctrl+K outside editable controls', async () => {
        const wrapper = mountSearch();

        window.dispatchEvent(
            new KeyboardEvent('keydown', { key: 'k', ctrlKey: true }),
        );
        await wrapper.vm.$nextTick();

        expect(
            wrapper.find('[data-test="global-search-dialog"]').exists(),
        ).toBe(true);
    });

    it('leaves Ctrl+K available inside editable controls', async () => {
        const wrapper = mountSearch();
        const input = document.createElement('input');
        document.body.append(input);

        input.dispatchEvent(
            new KeyboardEvent('keydown', {
                key: 'k',
                ctrlKey: true,
                bubbles: true,
            }),
        );
        await wrapper.vm.$nextTick();

        expect(
            wrapper.find('[data-test="global-search-dialog"]').exists(),
        ).toBe(false);
        input.remove();
    });

    it('debounces documentation searches until two characters', async () => {
        const wrapper = mountSearch();
        const input = await openPalette(wrapper);

        await input.setValue('a');
        await vi.advanceTimersByTimeAsync(300);
        expect(httpState.submissions).toHaveLength(0);

        await input.setValue('account');
        await vi.advanceTimersByTimeAsync(249);
        expect(httpState.submissions).toHaveLength(0);
        await vi.advanceTimersByTimeAsync(1);
        await flushPromises();

        expect(httpState.submissions).toHaveLength(1);
        expect(httpState.submissions[0]?.data).toEqual({ query: 'account' });
        expect(httpState.submissions[0]?.href).toMatchObject({
            url: '/documentation/search',
            method: 'get',
        });
    });

    it('cancels a superseded request before scheduling the next one', async () => {
        const wrapper = mountSearch();
        const input = await openPalette(wrapper);

        await input.setValue('account');
        await vi.advanceTimersByTimeAsync(250);
        await flushPromises();
        const cancelledBefore = httpState.cancelled;

        await input.setValue('account setup');

        expect(httpState.cancelled).toBeGreaterThan(cancelledBefore);
    });

    it('renders at most the results the server returned', async () => {
        httpState.response = {
            data: [
                {
                    id: 'doc-1',
                    title: 'Reset password',
                    slug: 'reset-password',
                    category: 'Account',
                    excerpt: 'How to reset a password.',
                },
            ],
        };
        const wrapper = mountSearch();
        const input = await openPalette(wrapper);

        await input.setValue('reset');
        await vi.advanceTimersByTimeAsync(250);
        await flushPromises();
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain('Reset password');
        expect(wrapper.text()).toContain(
            'documentation.search.group.documentation',
        );
    });
});
