import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';
import { nextTick } from 'vue';
import ChatMessageBubble from '@/components/chat/ChatMessageBubble.vue';
import type { ChatMessage } from '@/types';

/**
 * Open the reaction picker and hand back its options.
 *
 * The popover teleports, so the options never appear inside the wrapper.
 */
const openPicker = async (
    wrapper: ReturnType<typeof mount>,
): Promise<HTMLElement[]> => {
    await wrapper.get('[data-test="chat-reaction-picker"]').trigger('click');
    await nextTick();

    return Array.from(
        document.body.querySelectorAll<HTMLElement>(
            '[data-test^="chat-reaction-option-"]',
        ),
    );
};

const message: ChatMessage = {
    id: 'message-1',
    conversation_id: 'conversation-1',
    sender_id: 'peer',
    body: 'A picture',
    image: { url: '/chat/messages/message-1/image' },
    reactions: [{ id: 'reaction-1', user_id: 'me', emoji: '❤️' }],
    created_at: '2026-08-01T05:00:00Z',
};

describe('ChatMessageBubble', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('renders image, reaction and peer reaction picker', () => {
        const wrapper = mount(ChatMessageBubble, {
            props: { message, currentUserId: 'me' },
        });

        expect(wrapper.find('[data-test="chat-message-image"]').exists()).toBe(
            true,
        );
        expect(wrapper.find('[data-test="chat-message-reaction"]').text()).toBe(
            '❤️',
        );
        expect(
            wrapper.find('[data-test="chat-reaction-picker"]').exists(),
        ).toBe(true);
    });

    it('emits the emoji chosen from the picker', async () => {
        const wrapper = mount(ChatMessageBubble, {
            props: {
                message: { ...message, reactions: [] },
                currentUserId: 'me',
            },
            attachTo: document.body,
        });

        const options = await openPicker(wrapper);

        expect(options.length).toBeGreaterThan(1);

        options[0].click();
        await nextTick();

        expect(wrapper.emitted('react')?.[0]?.[1]).toBe('👍');
    });

    it('clears the reaction when the one already chosen is chosen again', async () => {
        const wrapper = mount(ChatMessageBubble, {
            props: {
                message: {
                    ...message,
                    reactions: [
                        { id: 'reaction-1', user_id: 'me', emoji: '👍' },
                    ],
                },
                currentUserId: 'me',
            },
            attachTo: document.body,
        });

        const options = await openPicker(wrapper);

        options[0].click();
        await nextTick();

        expect(wrapper.emitted('react')?.[0]?.[1]).toBeNull();
    });

    it('shows read state only on the latest outgoing message', () => {
        const wrapper = mount(ChatMessageBubble, {
            props: {
                message: {
                    ...message,
                    sender_id: 'me',
                    image: null,
                    reactions: [],
                },
                currentUserId: 'me',
                latestOutgoing: true,
                read: true,
            },
        });

        expect(wrapper.find('[data-test="chat-read-receipt"]').text()).toBe(
            'chat.label.read',
        );
        expect(
            wrapper.find('[data-test="chat-reaction-picker"]').exists(),
        ).toBe(false);
    });
});
