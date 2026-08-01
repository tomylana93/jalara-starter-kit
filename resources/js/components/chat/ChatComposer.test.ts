import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import ChatComposer from '@/components/chat/ChatComposer.vue';

describe('ChatComposer', () => {
    it('accepts one image and keeps the draft until sending succeeds', async () => {
        const createObjectURL = vi
            .spyOn(URL, 'createObjectURL')
            .mockReturnValue('blob:preview');
        vi.spyOn(URL, 'revokeObjectURL').mockImplementation(() => undefined);

        const wrapper = mount(ChatComposer, {
            props: { modelValue: 'Hello', imageUploadsEnabled: true },
        });
        const file = new File(['image'], 'photo.png', { type: 'image/png' });

        const input = wrapper.get('[data-test="chat-image-input"]');
        Object.defineProperty(input.element, 'files', { value: [file] });
        await input.trigger('change');
        expect(wrapper.find('[data-test="chat-image-draft"]').exists()).toBe(
            true,
        );

        await wrapper.get('form').trigger('submit');
        const emission = wrapper.emitted('send')?.[0];

        expect(emission?.[0]).toMatchObject({ body: 'Hello', image: file });
        expect(wrapper.find('[data-test="chat-image-draft"]').exists()).toBe(
            true,
        );

        (emission?.[1] as (succeeded: boolean) => void)(true);
        await wrapper.vm.$nextTick();

        expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual(['']);
        expect(wrapper.find('[data-test="chat-image-draft"]').exists()).toBe(
            false,
        );
        expect(createObjectURL).toHaveBeenCalledWith(file);
    });

    it('removes only the selected image when uploads are disabled', async () => {
        vi.spyOn(URL, 'createObjectURL').mockReturnValue('blob:preview');
        vi.spyOn(URL, 'revokeObjectURL').mockImplementation(() => undefined);

        const wrapper = mount(ChatComposer, {
            props: { modelValue: 'Text remains', imageUploadsEnabled: true },
        });
        const input = wrapper.get('[data-test="chat-image-input"]');
        Object.defineProperty(input.element, 'files', {
            value: [new File(['image'], 'photo.webp', { type: 'image/webp' })],
        });
        await input.trigger('change');

        await wrapper.setProps({ imageUploadsEnabled: false });

        expect(wrapper.find('[data-test="chat-image-draft"]').exists()).toBe(
            false,
        );
        expect(wrapper.text()).toContain('chat.message.image_removed_disabled');
        expect(wrapper.props('modelValue')).toBe('Text remains');
    });
});
