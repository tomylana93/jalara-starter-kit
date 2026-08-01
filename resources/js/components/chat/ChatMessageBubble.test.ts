import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import ChatMessageBubble from '@/components/chat/ChatMessageBubble.vue';
import type { ChatMessage } from '@/types';

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
