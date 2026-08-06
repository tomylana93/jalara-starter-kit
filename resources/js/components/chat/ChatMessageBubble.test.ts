import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';
import { nextTick } from 'vue';
import ChatMessageBubble from '@/components/chat/ChatMessageBubble.vue';
import { TEST_TIME_ZONE, withTimeZone } from '@/test/timeZone';
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

    it('marks every outgoing message with its delivery state', () => {
        const outgoing = {
            ...message,
            sender_id: 'me',
            image: null,
            reactions: [],
        };

        const read = mount(ChatMessageBubble, {
            props: { message: outgoing, currentUserId: 'me', read: true },
        });

        expect(
            read
                .get('[data-test="chat-read-receipt"]')
                .attributes('aria-label'),
        ).toBe('chat.label.read');
        expect(read.find('[data-test="chat-reaction-picker"]').exists()).toBe(
            false,
        );

        const sent = mount(ChatMessageBubble, {
            props: { message: outgoing, currentUserId: 'me', read: false },
        });

        expect(
            sent
                .get('[data-test="chat-read-receipt"]')
                .attributes('aria-label'),
        ).toBe('chat.label.sent');
    });

    /*
     * `app.css` paints every lucide icon with the brand color, and an outgoing
     * bubble is painted with that same color. Only an explicit utility on the
     * icon keeps the marks readable, and nothing but this assertion notices when
     * it goes missing: the failure is invisible ink, not a broken render.
     */
    it('keeps the delivery marks off the brand color', () => {
        const wrapper = mount(ChatMessageBubble, {
            props: {
                message: { ...message, sender_id: 'me', image: null },
                currentUserId: 'me',
                read: true,
            },
        });

        expect(
            wrapper.get('[data-test="chat-read-receipt"] svg').classes(),
        ).toContain('text-current');
    });

    it('renders the sent time on a 24 hour clock', () => {
        withTimeZone(TEST_TIME_ZONE, () => {
            const wrapper = mount(ChatMessageBubble, {
                props: {
                    message: {
                        ...message,
                        image: null,
                        created_at: '2026-08-01T10:05:00.000000Z',
                    },
                    currentUserId: 'me',
                },
            });

            /* A locale clock would read "5:05 PM" here. */
            expect(wrapper.text()).toContain('17:05');
        });
    });

    it('leaves an incoming message without a delivery state', () => {
        const wrapper = mount(ChatMessageBubble, {
            props: {
                message: { ...message, image: null },
                currentUserId: 'me',
            },
        });

        expect(wrapper.find('[data-test="chat-read-receipt"]').exists()).toBe(
            false,
        );
    });

    it('keeps the body free of the trailing meta', () => {
        const wrapper = mount(ChatMessageBubble, {
            props: {
                message: { ...message, image: null },
                currentUserId: 'me',
            },
        });

        expect(wrapper.get('[data-test="chat-message-body"]').text()).toBe(
            'A picture',
        );
    });
});
