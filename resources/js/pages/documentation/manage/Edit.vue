<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted } from 'vue';
import RichTextEditor from '@/components/editor/RichTextEditor.vue';
import InputError from '@/components/InputError.vue';
import PageWrapper from '@/components/PageWrapper.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { translate, useTranslations } from '@/composables/useTranslations';
import { index as documentationIndex } from '@/routes/documentation';
import { create, index as manageIndex } from '@/routes/documentation/manage';
import { edit, store, update } from '@/routes/documentation/manage/documents';
import type {
    DocumentationCategoryOption,
    DocumentationEditorValue,
    DocumentationStatus,
} from '@/types/documentation';
import type { RichTextDocument } from '@/types/editor';

type LayoutProps = {
    locale: string;
    fallbackLocale: string;
    documentation: DocumentationEditorValue | null;
};

const props = defineProps<{
    documentation: DocumentationEditorValue | null;
    categories: DocumentationCategoryOption[];
    statuses: { value: DocumentationStatus; label: string }[];
}>();

defineOptions({
    layout: (layoutProps: LayoutProps) => ({
        breadcrumbs: [
            {
                title: translate(
                    'documentation.title',
                    layoutProps.locale,
                    layoutProps.fallbackLocale,
                ),
                href: documentationIndex(),
            },
            {
                title: translate(
                    'documentation.manage.title',
                    layoutProps.locale,
                    layoutProps.fallbackLocale,
                ),
                href: manageIndex(),
            },
            {
                title: translate(
                    layoutProps.documentation
                        ? 'documentation.form.edit'
                        : 'documentation.form.create',
                    layoutProps.locale,
                    layoutProps.fallbackLocale,
                ),
                href: layoutProps.documentation
                    ? edit(layoutProps.documentation.slug)
                    : create(),
            },
        ],
    }),
});

const { t } = useTranslations();
const pageTitle = computed(() =>
    t(
        props.documentation
            ? 'documentation.form.edit'
            : 'documentation.form.create',
    ),
);
const form = useForm({
    documentation_category_id:
        props.documentation?.documentation_category_id ??
        props.categories[0]?.id ??
        '',
    title: props.documentation?.title ?? '',
    slug: props.documentation?.slug ?? '',
    status: props.documentation?.status ?? 'draft',
    content: JSON.stringify(
        props.documentation?.content ?? {
            type: 'doc',
            content: [{ type: 'paragraph' }],
        },
    ),
});
const editorContent = computed<RichTextDocument>({
    get: () => JSON.parse(form.content) as RichTextDocument,
    set: (value) => {
        form.content = JSON.stringify(value);
    },
});
let isSubmitting = false;

function submit(): void {
    isSubmitting = true;
    const options = {
        preserveScroll: true,
        onSuccess: () => form.defaults(),
        onFinish: () => {
            isSubmitting = false;
        },
    };

    if (props.documentation) {
        form.submit(update(props.documentation.slug), options);
    } else {
        form.submit(store(), options);
    }
}
function beforeUnload(event: BeforeUnloadEvent): void {
    if (form.isDirty && !isSubmitting) {
        event.preventDefault();
    }
}
const removeBeforeNavigation = router.on('before', (event) => {
    if (
        form.isDirty &&
        !isSubmitting &&
        !window.confirm(t('documentation.form.message.discard'))
    ) {
        event.preventDefault();
    }
});
onMounted(() => window.addEventListener('beforeunload', beforeUnload));
onBeforeUnmount(() => {
    removeBeforeNavigation();
    window.removeEventListener('beforeunload', beforeUnload);
});
</script>

<template>
    <div class="contents">
        <Head :title="pageTitle" />
        <PageWrapper
            :title="pageTitle"
            :description="t('documentation.form.description')"
        >
            <form class="flex flex-col gap-6" @submit.prevent="submit">
                <div class="grid gap-5 md:grid-cols-2">
                    <div class="flex flex-col gap-2">
                        <Label for="title">{{
                            t('documentation.form.label.title')
                        }}</Label
                        ><Input
                            id="title"
                            v-model="form.title"
                            :aria-invalid="Boolean(form.errors.title)"
                        />
                        <InputError :message="form.errors.title" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <Label for="slug">{{
                            t('documentation.form.label.slug')
                        }}</Label
                        ><Input
                            id="slug"
                            v-model="form.slug"
                            :disabled="Boolean(documentation?.published_at)"
                            :aria-invalid="Boolean(form.errors.slug)"
                        />
                        <InputError :message="form.errors.slug" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <Label>{{
                            t('documentation.form.label.category')
                        }}</Label>
                        <Select v-model="form.documentation_category_id"
                            ><SelectTrigger
                                data-test="documentation-category-trigger"
                                ><SelectValue
                                    :placeholder="
                                        t(
                                            'documentation.form.placeholder.category',
                                        )
                                    " /></SelectTrigger
                            ><SelectContent
                                ><SelectGroup
                                    ><SelectItem
                                        v-for="category in categories"
                                        :key="category.id"
                                        :value="category.id"
                                        data-test="documentation-category-option"
                                        >{{ category.name }}</SelectItem
                                    ></SelectGroup
                                ></SelectContent
                            ></Select
                        >
                        <InputError
                            :message="form.errors.documentation_category_id"
                        />
                    </div>
                    <div class="flex flex-col gap-2">
                        <Label>{{
                            t('documentation.form.label.status')
                        }}</Label>
                        <Select v-model="form.status"
                            ><SelectTrigger
                                data-test="documentation-status-trigger"
                                ><SelectValue /></SelectTrigger
                            ><SelectContent
                                ><SelectGroup
                                    ><SelectItem
                                        v-for="status in statuses"
                                        :key="status.value"
                                        :value="status.value"
                                        :data-test="`documentation-status-${status.value}`"
                                        >{{ status.label }}</SelectItem
                                    ></SelectGroup
                                ></SelectContent
                            ></Select
                        >
                        <InputError :message="form.errors.status" />
                    </div>
                </div>
                <RichTextEditor v-model="editorContent" />
                <InputError :message="form.errors.content" />
                <div class="flex justify-end gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        @click="router.visit(manageIndex())"
                        >{{ t('documentation.button.cancel') }}</Button
                    ><Button
                        type="submit"
                        :disabled="form.processing"
                        data-test="save-documentation"
                        >{{ t('documentation.button.save') }}</Button
                    >
                </div>
            </form>
        </PageWrapper>
    </div>
</template>
