import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import ErrorPage from './ErrorPage.vue';

type LayoutProps = {
    title: string;
    description: string;
    icon: unknown;
};

const layoutProps = (status: number): LayoutProps =>
    (
        ErrorPage as unknown as {
            layout: (props: {
                locale: string;
                fallbackLocale: string;
                status: number;
            }) => LayoutProps;
        }
    ).layout({ locale: 'en', fallbackLocale: 'en', status });

describe('error page', () => {
    /* `useTranslations` is mocked globally to echo the key, so copy is asserted as keys. */
    it('describes each handled status to the layout', () => {
        expect(layoutProps(403).title).toBe('error.forbidden.title');
        expect(layoutProps(404).title).toBe('error.not_found.title');
        expect(layoutProps(500).title).toBe('error.server_error.title');
    });

    it('gives each handled status its own icon', () => {
        const icons = [403, 404, 500].map((status) => layoutProps(status).icon);

        expect(new Set(icons).size).toBe(3);
    });

    /*
     * The handler forwards a fixed list today, but a status without an entry
     * must still produce a readable screen rather than an empty one.
     */
    it('falls back to the generic failure copy for an unmapped status', () => {
        const props = layoutProps(418);

        expect(props.title).toBe('error.server_error.title');
        expect(props.icon).toBe(layoutProps(500).icon);
    });

    it('links back to the home dispatcher', () => {
        const wrapper = mount(ErrorPage, { props: { status: 404 } });

        expect(wrapper.get('[data-test="error-home"]').attributes('href')).toBe(
            '/',
        );
    });
});
