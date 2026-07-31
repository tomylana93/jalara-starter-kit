import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import { inertiaPageProps } from '@/test/setup';
import type {
    NotificationFilter,
    NotificationItem,
    NotificationPayload,
} from '@/types';
import Index from './Index.vue';

const item = (overrides: Partial<NotificationItem> = {}): NotificationItem => ({
    id: 'a1',
    type: 'test',
    title: 'Deploy finished',
    message: 'The release is live.',
    url: '/dashboard',
    read_at: null,
    created_at: '2026-07-30T10:00:00+00:00',
    ...overrides,
});

const payload = (
    items: NotificationItem[],
    meta: Partial<NotificationPayload['meta']> = {},
): NotificationPayload => ({
    data: items,
    meta: {
        page: 1,
        perPage: 10,
        total: items.length,
        lastPage: 1,
        from: items.length === 0 ? null : 1,
        to: items.length === 0 ? null : items.length,
        ...meta,
    },
});

/** Router targets may be a string or a Wayfinder url/method pair. */
const targetUrl = (target: unknown): string =>
    typeof target === 'object' && target !== null && 'url' in target
        ? String((target as { url: string }).url)
        : String(target);

const mountPage = (
    notifications: NotificationPayload,
    filter: NotificationFilter = 'all',
) => mount(Index, { props: { notifications, filter } });

beforeEach(() => {
    inertiaPageProps.auth.user = { id: 'user-1', name: 'Ada' };
});

afterEach(() => {
    vi.restoreAllMocks();
    inertiaPageProps.auth.user = null;
});

it('renders the notifications with their unread state', () => {
    const wrapper = mountPage(
        payload([
            item({ id: 'a1', title: 'First' }),
            item({
                id: 'a2',
                title: 'Second',
                read_at: '2026-07-30T11:00:00+00:00',
            }),
        ]),
    );

    expect(wrapper.text()).toContain('First');
    expect(wrapper.text()).toContain('Second');
    expect(
        wrapper
            .get('[data-test="notification-row-a1"]')
            .attributes('data-unread'),
    ).toBe('true');
    expect(
        wrapper
            .get('[data-test="notification-row-a2"]')
            .attributes('data-unread'),
    ).toBe('false');
});

it('marks the active filter as the current page', () => {
    const wrapper = mountPage(payload([item()]), 'unread');

    expect(
        wrapper
            .get('[data-test="notification-filter-unread"]')
            .attributes('aria-current'),
    ).toBe('page');
    expect(
        wrapper
            .get('[data-test="notification-filter-all"]')
            .attributes('aria-current'),
    ).toBeUndefined();
});

it('links each filter to its server side query', () => {
    const wrapper = mountPage(payload([item()]));

    expect(
        wrapper.get('[data-test="notification-filter-all"]').attributes('href'),
    ).toBe('/notifications');
    expect(
        decodeURIComponent(
            wrapper
                .get('[data-test="notification-filter-unread"]')
                .attributes('href') ?? '',
        ),
    ).toBe('/notifications?filter=unread');
});

it('shows an empty state describing the active filter', () => {
    const wrapper = mountPage(payload([]), 'unread');

    expect(wrapper.find('[data-test="notification-empty"]').exists()).toBe(
        true,
    );
    expect(wrapper.find('[data-test="notification-list"]').exists()).toBe(
        false,
    );
    expect(
        wrapper.get('[data-test="notification-empty-description"]').text(),
    ).toBe('notification.empty.unread');
});

it('summarises the paginated window', () => {
    const wrapper = mountPage(
        payload(
            Array.from({ length: 10 }, (_, index) => item({ id: `a${index}` })),
            {
                total: 12,
                lastPage: 2,
                to: 10,
            },
        ),
    );

    expect(wrapper.find('[data-test="notification-summary"]').exists()).toBe(
        true,
    );
    expect(wrapper.findAll('[data-test^="notification-row-"]').length).toBe(10);
});

it('hides pagination when a single page holds everything', () => {
    const wrapper = mountPage(payload([item()]));

    expect(wrapper.find('[data-test="notification-next-page"]').exists()).toBe(
        false,
    );
});

it('renders pagination when the history spans pages', () => {
    const wrapper = mountPage(
        payload([item()], { total: 12, lastPage: 2, to: 10 }),
    );

    expect(wrapper.find('[data-test="notification-next-page"]').exists()).toBe(
        true,
    );
});

it('keeps the filter when paging', async () => {
    const get = vi.spyOn(router, 'get').mockImplementation(() => undefined);

    const wrapper = mountPage(
        payload([item()], { total: 12, lastPage: 2, to: 10 }),
        'unread',
    );

    await wrapper.get('[data-test="notification-next-page"]').trigger('click');

    expect(decodeURIComponent(targetUrl(get.mock.calls[0]?.[0]))).toBe(
        '/notifications?filter=unread&page=2',
    );
});

it('marks a single notification as read', async () => {
    const patch = vi.spyOn(router, 'patch').mockImplementation(() => undefined);

    const wrapper = mountPage(payload([item({ id: 'a1' })]));
    await wrapper.get('[data-test="notification-read-a1"]').trigger('click');

    expect(patch.mock.calls[0]?.[0]).toMatchObject({
        url: '/notifications/a1/read',
    });
});

it('marks everything as read', async () => {
    const patch = vi.spyOn(router, 'patch').mockImplementation(() => undefined);

    const wrapper = mountPage(payload([item()]));
    await wrapper.get('[data-test="notification-mark-all"]').trigger('click');

    expect(patch.mock.calls[0]?.[0]).toMatchObject({
        url: '/notifications/read-all',
    });
});

it('hides the read actions when everything is already read', () => {
    const wrapper = mountPage(
        payload([item({ id: 'a1', read_at: '2026-07-30T11:00:00+00:00' })]),
    );

    expect(wrapper.find('[data-test="notification-mark-all"]').exists()).toBe(
        false,
    );
    expect(wrapper.find('[data-test="notification-read-a1"]').exists()).toBe(
        false,
    );
});

it('opens a notification that carries a url and records the read', async () => {
    const patch = vi.spyOn(router, 'patch').mockImplementation(() => undefined);
    const visit = vi.spyOn(router, 'visit').mockImplementation(() => undefined);

    const wrapper = mountPage(payload([item({ id: 'a1', url: '/dashboard' })]));
    await wrapper.get('[data-test="notification-open-a1"]').trigger('click');

    expect(patch.mock.calls[0]?.[0]).toMatchObject({
        url: '/notifications/a1/read',
    });
    expect(visit).toHaveBeenCalledWith('/dashboard');
});

it('offers no open action for a notification without a url', () => {
    const wrapper = mountPage(payload([item({ id: 'a1', url: null })]));

    expect(wrapper.find('[data-test="notification-open-a1"]').exists()).toBe(
        false,
    );
    expect(wrapper.find('[data-test="notification-row-a1"]').exists()).toBe(
        true,
    );
});
