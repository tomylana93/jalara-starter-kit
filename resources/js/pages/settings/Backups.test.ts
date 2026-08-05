import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import { AlertDialog } from '@/components/ui/alert-dialog';
import Index from './Backups.vue';

const routerPost = vi.spyOn(router, 'post').mockImplementation(() => undefined);
const routerDelete = vi
    .spyOn(router, 'delete')
    .mockImplementation(() => undefined);
const routerReload = vi
    .spyOn(router, 'reload')
    .mockImplementation(() => undefined);

const archive = {
    filename: '2026-01-01-00-00-00.zip',
    disk: 'backups',
    size_in_bytes: 2048,
    created_at: '2026-01-01T00:00:00.000000Z',
};

const completedRun = {
    id: 'run-1',
    status: 'completed' as const,
    filename: archive.filename,
    size_in_bytes: 2048,
    error_code: null,
    started_by: 'Ada Lovelace',
    started_at: '2026-01-01T00:00:00.000000Z',
    completed_at: '2026-01-01T00:05:00.000000Z',
    created_at: '2026-01-01T00:00:00.000000Z',
};

const mountIndex = (overrides: Record<string, unknown> = {}) =>
    mount(Index, {
        props: {
            dateFormat: 'd/m/Y',
            archives: [archive],
            runs: [completedRun],
            activeRun: null,
            ...overrides,
        },
    });

beforeEach(() => {
    vi.useFakeTimers();
    routerPost.mockClear();
    routerDelete.mockClear();
    routerReload.mockClear();
});

afterEach(() => {
    vi.useRealTimers();
});

it('lists the archives with a download link per row', () => {
    const wrapper = mountIndex();

    expect(wrapper.findAll('[data-test="archive-row"]')).toHaveLength(1);
    expect(
        wrapper.find('[data-test="download-archive"]').attributes('href'),
    ).toBe(`/settings/backups/${archive.filename}/download`);
});

it('shows an empty state when nothing has been archived yet', () => {
    const wrapper = mountIndex({ archives: [] });

    expect(wrapper.findAll('[data-test="archive-row"]')).toHaveLength(0);
    expect(wrapper.text()).toContain('backup.archive.empty');
});

it('starts a backup', async () => {
    const wrapper = mountIndex();

    await wrapper.find('[data-test="run-backup"]').trigger('click');

    expect(routerPost).toHaveBeenCalledOnce();
    expect(routerPost.mock.calls[0]?.[0]).toBe('/settings/backups');
});

/*
 * The button is the only way to start a run from the page, so disabling it while
 * one is active is what stops an administrator queuing a second request that the
 * server would only reject anyway.
 */
it('disables the run button while a backup is active', () => {
    const wrapper = mountIndex({
        activeRun: { ...completedRun, status: 'running' },
    });

    expect(
        wrapper.find('[data-test="run-backup"]').attributes('disabled'),
    ).toBeDefined();
});

it('deletes an archive only after the confirmation is accepted', async () => {
    const wrapper = mountIndex();

    await wrapper.find('[data-test="delete-archive"]').trigger('click');

    expect(routerDelete).not.toHaveBeenCalled();

    await wrapper.find('[data-test="confirm-delete-archive"]').trigger('click');

    expect(routerDelete).toHaveBeenCalledOnce();
    expect(routerDelete.mock.calls[0]?.[0]).toBe(
        `/settings/backups/${archive.filename}`,
    );
});

/*
 * `AlertDialogAction` forwards `@click` as a fallthrough attribute, so Reka's own
 * close handler runs before the page's confirm handler. This drives that exact
 * order: the dialog is closed first, and the request must still be sent. Getting
 * this wrong sends nothing at all, with no error anywhere to show for it.
 */
it('deletes even when the dialog closes before the confirm handler runs', async () => {
    const wrapper = mountIndex();

    await wrapper.find('[data-test="delete-archive"]').trigger('click');

    const dialog = wrapper.findComponent(AlertDialog);
    dialog.vm.$emit('update:open', false);
    await wrapper.vm.$nextTick();

    await wrapper.find('[data-test="confirm-delete-archive"]').trigger('click');

    expect(routerDelete).toHaveBeenCalledOnce();
    expect(routerDelete.mock.calls[0]?.[0]).toBe(
        `/settings/backups/${archive.filename}`,
    );
});

it('renders the failure reason of a failed run', () => {
    const wrapper = mountIndex({
        runs: [{ ...completedRun, status: 'failed', error_code: 'failed' }],
    });

    expect(wrapper.find('[data-test="run-error"]').text()).toBe(
        'backup.error.failed',
    );
});

/*
 * An interval left running on an idle admin page is a request leak that only
 * shows up in the logs, so polling must exist exactly as long as a run does.
 */
it('polls only the changing props while a run is active', async () => {
    const wrapper = mountIndex({
        activeRun: { ...completedRun, status: 'running' },
    });

    await vi.advanceTimersByTimeAsync(5000);

    expect(routerReload).toHaveBeenCalledOnce();
    expect(routerReload.mock.calls[0]?.[0]).toEqual({
        only: ['activeRun', 'runs', 'archives'],
    });

    await wrapper.setProps({ activeRun: null });
    routerReload.mockClear();

    await vi.advanceTimersByTimeAsync(15000);

    expect(routerReload).not.toHaveBeenCalled();
});

it('does not poll on an idle page', async () => {
    mountIndex();

    await vi.advanceTimersByTimeAsync(30000);

    expect(routerReload).not.toHaveBeenCalled();
});
