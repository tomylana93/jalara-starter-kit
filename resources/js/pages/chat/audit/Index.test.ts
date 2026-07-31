import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import AuditIndex from '@/pages/chat/audit/Index.vue';
import type { ChatAuditConversation } from '@/types';

const conversation = (id: string, names: string[]): ChatAuditConversation => ({
    id,
    participants: names.map((name, index) => ({
        id: `${id}-${index}`,
        name,
        avatar: null,
        role: null,
        available: true,
    })),
    last_message_at: '2026-07-31T00:00:00+00:00',
    message_count: 3,
});

const mountPage = (
    rows: ChatAuditConversation[],
    search: string | null = null,
) =>
    mount(AuditIndex, {
        props: { conversations: { data: rows }, search },
    });

describe('chat/audit/Index', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.spyOn(router, 'visit').mockImplementation(() => undefined);
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.restoreAllMocks();
    });

    it('lists the audited conversations through an infinite scroll container', () => {
        const wrapper = mountPage([
            conversation('c-1', ['Amelia Stone', 'Bruno Vega']),
        ]);

        expect(
            wrapper.find('[data-infinite-scroll="conversations"]').exists(),
        ).toBe(true);
        expect(wrapper.find('[data-test="chat-audit-list"]').text()).toContain(
            'Amelia Stone',
        );
    });

    it('prefills the search box from the server', () => {
        const wrapper = mountPage(
            [conversation('c-1', ['Amelia Stone', 'Bruno Vega'])],
            'Amelia',
        );

        expect(
            wrapper.get('[data-test="chat-audit-search"]').attributes('value'),
        ).toBe('Amelia');
    });

    it('sends the participant term to the server and resets the merged pages', async () => {
        const wrapper = mountPage([
            conversation('c-1', ['Amelia Stone', 'Bruno Vega']),
        ]);

        await wrapper.get('[data-test="chat-audit-search"]').setValue('Amelia');

        vi.runAllTimers();

        expect(router.visit).toHaveBeenCalledTimes(1);

        const [href, options] = vi.mocked(router.visit).mock
            .calls[0] as unknown as [
            { url: string },
            { only: string[]; reset: string[] },
        ];

        expect(href.url).toContain('search=Amelia');
        expect(options.only).toContain('conversations');
        expect(options.reset).toContain('conversations');
    });

    it('explains an empty result differently when a term was used', () => {
        expect(
            mountPage([]).find('[data-test="chat-audit-empty"]').text(),
        ).toBe('chat.audit.empty.conversations');
        expect(
            mountPage([], 'Zeno').find('[data-test="chat-audit-empty"]').text(),
        ).toBe('chat.audit.empty.search');
    });
});
