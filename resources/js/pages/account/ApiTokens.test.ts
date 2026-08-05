import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import {
    inertiaPageFlash,
    resetFormState,
    resetInertiaPageFlash,
} from '@/test/setup';
import type { ApiToken } from '@/types/apiTokens';
import ApiTokens from './ApiTokens.vue';

const token = (overrides: Partial<ApiToken> = {}): ApiToken => ({
    id: 'token-1',
    name: 'laptop',
    last_used_at: null,
    created_at: '2026-08-05T00:00:00+00:00',
    ...overrides,
});

describe('api tokens page', () => {
    beforeEach(() => {
        resetFormState();
        resetInertiaPageFlash();
    });

    it('shows the empty state when no token exists', () => {
        const wrapper = mount(ApiTokens, { props: { tokens: [] } });

        expect(wrapper.find('[data-test="api-token-empty"]').exists()).toBe(
            true,
        );
    });

    it('lists a token with its name and never-used marker', () => {
        const wrapper = mount(ApiTokens, { props: { tokens: [token()] } });
        const row = wrapper.get('[data-test="api-token-row-token-1"]');

        expect(wrapper.find('[data-test="api-token-empty"]').exists()).toBe(
            false,
        );
        expect(row.text()).toContain('laptop');
        expect(row.text()).toContain('account.api_token.label.never_used');
    });

    it('shows the plain text only while it is flashed', () => {
        const without = mount(ApiTokens, { props: { tokens: [token()] } });

        expect(
            without.find('[data-test="api-token-plain-text"]').exists(),
        ).toBe(false);

        inertiaPageFlash.createdApiToken = {
            name: 'laptop',
            plainText: '1|secret-value',
        };

        const withFlash = mount(ApiTokens, { props: { tokens: [token()] } });

        expect(
            withFlash.get('[data-test="api-token-plain-text"]').text(),
        ).toContain('1|secret-value');
    });

    it('keeps token name validation on the backend', () => {
        const wrapper = mount(ApiTokens, { props: { tokens: [] } });
        const name = wrapper.get('[data-test="api-token-name"]');

        expect(name.attributes('required')).toBeUndefined();
    });

    it('puts revoking behind a confirmation rather than a single click', () => {
        const wrapper = mount(ApiTokens, { props: { tokens: [token()] } });
        const trigger = wrapper.get('[data-test="api-token-revoke-token-1"]');

        expect(trigger.attributes('aria-haspopup')).toBe('dialog');
        expect(trigger.attributes('aria-expanded')).toBe('false');
        expect(
            wrapper
                .find('[data-test="api-token-confirm-revoke-token-1"]')
                .exists(),
        ).toBe(false);
    });
});
