import { mount } from '@vue/test-utils';
import { expect, it } from 'vitest';
import { inertiaPageProps } from '@/test/setup';
import AppHeader from './AppHeader.vue';

it('renders the appearance toggle in the header actions', () => {
    inertiaPageProps.auth = { user: { name: 'Ada Lovelace' } };

    const wrapper = mount(AppHeader);

    expect(wrapper.find('[data-test="appearance-toggle"]').exists()).toBe(true);
});
