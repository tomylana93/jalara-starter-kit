import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { inertiaPageProps } from '@/test/setup';
import Maintenance from './Maintenance.vue';

type LayoutProps = {
    title: string;
    description: string;
    icon: unknown;
};

const layoutProps = (): LayoutProps =>
    (
        Maintenance as unknown as {
            layout: (props: {
                locale: string;
                fallbackLocale: string;
            }) => LayoutProps;
        }
    ).layout({ locale: 'en', fallbackLocale: 'en' });

describe('maintenance page', () => {
    afterEach(() => {
        /* A plain object shared across tests, so both flags go back to default. */
        inertiaPageProps.auth.user = null;
        inertiaPageProps.can.manageSettings = true;
        vi.restoreAllMocks();
    });

    /* `useTranslations` is mocked globally to echo the key, so copy is asserted as keys. */
    it('describes itself to the layout with an icon', () => {
        const props = layoutProps();

        expect(props.title).toBe('maintenance.title');
        expect(props.description).toBe('maintenance.description');
        expect(props.icon).toBeTruthy();
    });

    it('reloads the current page from the retry action', async () => {
        const reload = vi.spyOn(router, 'reload').mockImplementation(() => {});

        const wrapper = mount(Maintenance);

        await wrapper.get('[data-test="maintenance-retry"]').trigger('click');

        expect(reload).toHaveBeenCalledOnce();
    });

    it('hides the sign-out action from a guest', () => {
        inertiaPageProps.auth.user = null;

        const wrapper = mount(Maintenance);

        expect(
            wrapper.find('[data-test="maintenance-sign-out"]').exists(),
        ).toBe(false);
    });

    it('offers the sign-out action to a session that survived the switch', () => {
        inertiaPageProps.auth.user = { id: 'user-1', name: 'Ada' };

        const wrapper = mount(Maintenance);

        expect(
            wrapper.find('[data-test="maintenance-sign-out"]').exists(),
        ).toBe(true);
    });

    it('offers the settings shortcut only to a settings manager', () => {
        inertiaPageProps.can.manageSettings = false;

        expect(
            mount(Maintenance)
                .find('[data-test="maintenance-settings"]')
                .exists(),
        ).toBe(false);

        inertiaPageProps.can.manageSettings = true;

        expect(
            mount(Maintenance)
                .get('[data-test="maintenance-settings"]')
                .attributes('href'),
        ).toBe('/settings/security');
    });
});
