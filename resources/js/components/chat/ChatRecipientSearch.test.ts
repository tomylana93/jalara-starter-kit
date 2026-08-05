import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import ChatRecipientSearch from '@/components/chat/ChatRecipientSearch.vue';
import type { ChatProfile } from '@/types';

const { searchRecipients } = vi.hoisted(() => ({
    searchRecipients: vi.fn(),
}));

vi.mock('@/composables/useChat', () => ({
    RECIPIENT_SEARCH_MINIMUM: 2,
    useChat: () => ({ searchRecipients }),
}));

const profile = (overrides: Partial<ChatProfile> = {}): ChatProfile =>
    ({
        id: 'user-1',
        name: 'Ada Lovelace',
        avatar: null,
        role: 'Engineer',
        available: true,
        ...overrides,
    }) as ChatProfile;

/**
 * Type into the search box and let the watcher's request settle.
 */
const search = async (
    wrapper: ReturnType<typeof mount>,
    term: string,
): Promise<void> => {
    await wrapper.get('[data-test="chat-recipient-search"]').setValue(term);
    await flushPromises();
};

describe('ChatRecipientSearch', () => {
    beforeEach(() => {
        searchRecipients.mockReset();
        searchRecipients.mockResolvedValue([]);
    });

    it('does not search below the minimum term length', async () => {
        const wrapper = mount(ChatRecipientSearch);

        await search(wrapper, 'a');

        expect(searchRecipients).not.toHaveBeenCalled();
        expect(wrapper.find('[data-test="chat-search-hint"]').exists()).toBe(
            true,
        );
        expect(wrapper.find('[data-test="chat-search-results"]').exists()).toBe(
            false,
        );
    });

    it('lists a match once the term is long enough', async () => {
        searchRecipients.mockResolvedValue([profile()]);

        const wrapper = mount(ChatRecipientSearch);

        await search(wrapper, 'ada');

        expect(searchRecipients).toHaveBeenCalledOnce();

        const result = wrapper.get('[data-test="chat-recipient-user-1"]');

        expect(result.text()).toContain('Ada Lovelace');
        expect(result.text()).toContain('Engineer');
    });

    it('reports an empty search rather than an empty list', async () => {
        const wrapper = mount(ChatRecipientSearch);

        await search(wrapper, 'zzz');

        expect(wrapper.find('[data-test="chat-search-empty"]').exists()).toBe(
            true,
        );
        expect(wrapper.find('[data-test="chat-search-results"]').exists()).toBe(
            false,
        );
    });

    it('emits the chosen recipient and clears the term', async () => {
        const recipient = profile();
        searchRecipients.mockResolvedValue([recipient]);

        const wrapper = mount(ChatRecipientSearch);

        await search(wrapper, 'ada');
        await wrapper
            .get('[data-test="chat-recipient-user-1"]')
            .trigger('click');
        await flushPromises();

        expect(wrapper.emitted('select')?.[0]).toEqual([recipient]);
        expect(
            wrapper.get<HTMLInputElement>('[data-test="chat-recipient-search"]')
                .element.value,
        ).toBe('');
        expect(wrapper.find('[data-test="chat-search-results"]').exists()).toBe(
            false,
        );
    });

    it('labels the search box for assistive technology', () => {
        const wrapper = mount(ChatRecipientSearch);
        const input = wrapper.get('[data-test="chat-recipient-search"]');

        expect(input.attributes('id')).toBe('chat-recipient-search');
        expect(wrapper.text()).toContain('chat.label.search');
    });
});
