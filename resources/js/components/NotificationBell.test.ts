import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
import { echoState, inertiaPageProps, resetEchoState } from '@/test/setup';
import type { NotificationItem } from '@/types';
import NotificationBell from './NotificationBell.vue';

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

beforeEach(() => {
    resetEchoState();
    inertiaPageProps.auth.user = { id: 'user-1', name: 'Ada' };
    inertiaPageProps.notificationBell = { items: [], unreadCount: 0 };
});

afterEach(() => {
    vi.restoreAllMocks();
    inertiaPageProps.auth.user = null;
});

it('subscribes to the private channel of the authenticated user', () => {
    mount(NotificationBell);

    expect(echoState.channel).toBe('App.Models.User.user-1');
});

it('renders no badge when nothing is unread', () => {
    inertiaPageProps.notificationBell = {
        items: [item({ read_at: '2026-07-30T11:00:00+00:00' })],
        unreadCount: 0,
    };

    const wrapper = mount(NotificationBell);

    expect(wrapper.find('[data-test="notification-badge"]').exists()).toBe(
        false,
    );
    expect(wrapper.find('[data-test="notification-mark-all"]').exists()).toBe(
        false,
    );
});

it('renders the unread count on the badge', () => {
    inertiaPageProps.notificationBell = { items: [item()], unreadCount: 3 };

    const wrapper = mount(NotificationBell);

    const badge = wrapper.find('[data-test="notification-badge"]');
    expect(badge.text()).toBe('3');
    expect(badge.classes()).toContain('bg-red-600');
    expect(badge.classes()).toContain('text-white');
    expect(badge.classes()).not.toContain('bg-primary');
    expect(badge.classes()).not.toContain('text-primary-foreground');
});

it('caps the badge at nine plus', () => {
    inertiaPageProps.notificationBell = { items: [item()], unreadCount: 24 };

    const wrapper = mount(NotificationBell);

    expect(wrapper.find('[data-test="notification-badge"]').text()).toBe('9+');
});

it('renders the shared notifications with their unread marker', () => {
    inertiaPageProps.notificationBell = {
        items: [
            item({ id: 'a1', title: 'First' }),
            item({
                id: 'a2',
                title: 'Second',
                read_at: '2026-07-30T11:00:00+00:00',
            }),
        ],
        unreadCount: 1,
    };

    const wrapper = mount(NotificationBell);

    expect(wrapper.text()).toContain('First');
    expect(wrapper.text()).toContain('Second');
    expect(
        wrapper
            .find('[data-test="notification-item-a1"]')
            .attributes('data-unread'),
    ).toBe('true');
    expect(
        wrapper
            .find('[data-test="notification-item-a2"]')
            .attributes('data-unread'),
    ).toBe('false');
});

it('renders at most five notifications', () => {
    inertiaPageProps.notificationBell = {
        items: Array.from({ length: 5 }, (_, index) =>
            item({ id: `a${index}` }),
        ),
        unreadCount: 5,
    };

    const wrapper = mount(NotificationBell);

    expect(wrapper.findAll('[role="menuitem"]').length).toBe(
        /* Five notifications plus the "view all" row. */
        6,
    );
});

it('shows an empty state when there is nothing to show', () => {
    const wrapper = mount(NotificationBell);

    expect(wrapper.find('[data-test="notification-empty"]').exists()).toBe(
        true,
    );
});

it('adds a broadcast notification without a page refresh', async () => {
    const wrapper = mount(NotificationBell);

    expect(wrapper.find('[data-test="notification-empty"]').exists()).toBe(
        true,
    );

    echoState.callback?.(item({ id: 'live-1', title: 'Arrived live' }));
    await nextTick();

    expect(wrapper.text()).toContain('Arrived live');
    expect(wrapper.find('[data-test="notification-empty"]').exists()).toBe(
        false,
    );
    expect(wrapper.find('[data-test="notification-badge"]').text()).toBe('1');
});

it('ignores a broadcast that repeats an id it already holds', async () => {
    const wrapper = mount(NotificationBell);

    echoState.callback?.(item({ id: 'live-1' }));
    echoState.callback?.(item({ id: 'live-1' }));
    await nextTick();

    expect(
        wrapper.findAll('[data-test="notification-item-live-1"]').length,
    ).toBe(1);
    expect(wrapper.find('[data-test="notification-badge"]').text()).toBe('1');
});

it('does not double count a broadcast the server already shared', async () => {
    inertiaPageProps.notificationBell = {
        items: [item({ id: 'live-1' })],
        unreadCount: 1,
    };

    const wrapper = mount(NotificationBell);

    echoState.callback?.(item({ id: 'live-1' }));
    await nextTick();

    expect(
        wrapper.findAll('[data-test="notification-item-live-1"]').length,
    ).toBe(1);
    expect(wrapper.find('[data-test="notification-badge"]').text()).toBe('1');
});

it('marks every notification as read from the dropdown', async () => {
    const patch = vi.spyOn(router, 'patch').mockImplementation(() => undefined);

    inertiaPageProps.notificationBell = { items: [item()], unreadCount: 2 };

    const wrapper = mount(NotificationBell);
    await wrapper.find('[data-test="notification-mark-all"]').trigger('click');

    expect(patch).toHaveBeenCalledTimes(1);
    expect(patch.mock.calls[0]?.[0]).toMatchObject({
        url: '/notifications/read-all',
    });
});

it('marks one notification as read and opens its destination', async () => {
    const patch = vi.spyOn(router, 'patch').mockImplementation(() => undefined);
    const visit = vi.spyOn(router, 'visit').mockImplementation(() => undefined);

    inertiaPageProps.notificationBell = {
        items: [item({ id: 'a1', url: '/dashboard' })],
        unreadCount: 1,
    };

    const wrapper = mount(NotificationBell);
    await wrapper.find('[data-test="notification-item-a1"]').trigger('click');

    expect(patch.mock.calls[0]?.[0]).toMatchObject({
        url: '/notifications/a1/read',
    });
    expect(visit).toHaveBeenCalledWith('/dashboard');
});

it('keeps a notification without a url safe to select', async () => {
    const patch = vi.spyOn(router, 'patch').mockImplementation(() => undefined);
    const visit = vi.spyOn(router, 'visit').mockImplementation(() => undefined);

    inertiaPageProps.notificationBell = {
        items: [item({ id: 'a1', url: null })],
        unreadCount: 1,
    };

    const wrapper = mount(NotificationBell);
    await wrapper.find('[data-test="notification-item-a1"]').trigger('click');

    expect(patch).toHaveBeenCalledTimes(1);
    expect(visit).not.toHaveBeenCalled();
});

it('does not re-mark a notification that is already read', async () => {
    const patch = vi.spyOn(router, 'patch').mockImplementation(() => undefined);
    const visit = vi.spyOn(router, 'visit').mockImplementation(() => undefined);

    inertiaPageProps.notificationBell = {
        items: [
            item({
                id: 'a1',
                url: '/dashboard',
                read_at: '2026-07-30T11:00:00+00:00',
            }),
        ],
        unreadCount: 0,
    };

    const wrapper = mount(NotificationBell);
    await wrapper.find('[data-test="notification-item-a1"]').trigger('click');

    expect(patch).not.toHaveBeenCalled();
    expect(visit).toHaveBeenCalledWith('/dashboard');
});

it('links to the full notification page', () => {
    const wrapper = mount(NotificationBell);

    expect(
        wrapper.find('[data-test="notification-view-all"]').attributes('href'),
    ).toBe('/notifications');
});
