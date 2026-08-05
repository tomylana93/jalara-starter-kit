<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Check, Copy } from '@lucide/vue';
import { computed, ref } from 'vue';
import ApiTokenController from '@/actions/App/Http/Controllers/Account/ApiTokenController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { translate, useTranslations } from '@/composables/useTranslations';
import { index } from '@/routes/account/api-tokens';
import type { ApiToken } from '@/types/apiTokens';

type Props = {
    tokens: ApiToken[];
};

type LayoutProps = {
    locale: string;
    fallbackLocale: string;
};

const props = defineProps<Props>();

defineOptions({
    /*
     * Inertia hands every shared prop to the page component, and these pages
     * render a fragment, so undeclared props would otherwise leak onto the DOM
     * as extraneous attributes.
     */
    inheritAttrs: false,
    layout: (props: LayoutProps) => ({
        breadcrumbs: [
            {
                title: translate(
                    'account.layout.label.api_tokens',
                    props.locale,
                    props.fallbackLocale,
                ),
                href: index(),
            },
        ],
    }),
});

const page = usePage();
const { t } = useTranslations();
const copied = ref(false);

/*
 * The plain text arrives as flash rather than a prop, so it survives exactly
 * one render and cannot be re-displayed by a partial reload.
 */
const createdToken = computed(() => page.flash.createdApiToken ?? null);

const formatted = (value: string | null): string =>
    value === null
        ? t('account.api_token.label.never_used')
        : new Intl.DateTimeFormat(page.props.locale, {
              dateStyle: 'medium',
          }).format(new Date(value));

async function copyToken(plainText: string): Promise<void> {
    await navigator.clipboard?.writeText(plainText);

    copied.value = true;
}
</script>

<template>
    <Head :title="t('account.layout.label.api_tokens')" />

    <h1 class="sr-only">{{ t('account.layout.label.api_tokens') }}</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            :title="t('account.api_token.title')"
            :description="t('account.api_token.description')"
        />

        <Alert v-if="createdToken !== null" data-test="api-token-plain-text">
            <AlertTitle>
                {{ t('account.api_token.label.plain_text') }}
            </AlertTitle>
            <AlertDescription class="space-y-3">
                <p>{{ t('account.api_token.message.copy_once') }}</p>

                <code
                    class="block w-full overflow-x-auto rounded-md bg-muted px-3 py-2 font-mono text-xs wrap-anywhere"
                >
                    {{ createdToken.plainText }}
                </code>

                <Button
                    variant="outline"
                    size="sm"
                    data-test="api-token-copy"
                    @click="copyToken(createdToken.plainText)"
                >
                    <Check v-if="copied" class="size-4" />
                    <Copy v-else class="size-4" />
                    {{
                        copied
                            ? t('account.api_token.button.copied')
                            : t('account.api_token.button.copy')
                    }}
                </Button>
            </AlertDescription>
        </Alert>

        <Form
            v-bind="ApiTokenController.store.form()"
            reset-on-success
            :options="{ preserveScroll: true }"
            class="flex flex-col gap-2 sm:flex-row sm:items-start"
            v-slot="{ errors, processing }"
        >
            <div class="grid flex-1 gap-2">
                <Label for="name" class="sr-only">
                    {{ t('account.api_token.label.name') }}
                </Label>
                <Input
                    id="name"
                    name="name"
                    :aria-invalid="Boolean(errors.name)"
                    :placeholder="t('account.api_token.placeholder.name')"
                    data-test="api-token-name"
                />
                <InputError :message="errors.name" />
            </div>

            <Button
                type="submit"
                :disabled="processing"
                data-test="api-token-create"
            >
                {{ t('account.api_token.button.create') }}
            </Button>
        </Form>

        <Table data-test="api-token-list">
            <TableHeader>
                <TableRow>
                    <TableHead>
                        {{ t('account.api_token.label.name') }}
                    </TableHead>
                    <TableHead>
                        {{ t('account.api_token.label.created') }}
                    </TableHead>
                    <TableHead>
                        {{ t('account.api_token.label.last_used') }}
                    </TableHead>
                    <TableHead class="w-0" />
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-if="props.tokens.length === 0">
                    <TableCell
                        colspan="4"
                        class="py-10 text-center"
                        data-test="api-token-empty"
                    >
                        <span class="block font-medium">
                            {{ t('account.api_token.empty.title') }}
                        </span>
                        <span class="mt-1 block text-sm text-muted-foreground">
                            {{ t('account.api_token.empty.description') }}
                        </span>
                    </TableCell>
                </TableRow>

                <TableRow
                    v-for="token in props.tokens"
                    :key="token.id"
                    :data-test="`api-token-row-${token.id}`"
                >
                    <TableCell class="font-medium">{{ token.name }}</TableCell>
                    <TableCell>{{ formatted(token.created_at) }}</TableCell>
                    <TableCell>{{ formatted(token.last_used_at) }}</TableCell>
                    <TableCell>
                        <Dialog>
                            <DialogTrigger as-child>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    :data-test="`api-token-revoke-${token.id}`"
                                >
                                    {{ t('account.api_token.button.revoke') }}
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <Form
                                    v-bind="
                                        ApiTokenController.destroy.form(
                                            token.id,
                                        )
                                    "
                                    :options="{ preserveScroll: true }"
                                    class="space-y-6"
                                    v-slot="{ processing }"
                                >
                                    <DialogHeader class="space-y-3">
                                        <DialogTitle>
                                            {{
                                                t(
                                                    'account.api_token.confirmation_title',
                                                )
                                            }}
                                        </DialogTitle>
                                        <DialogDescription>
                                            {{
                                                t(
                                                    'account.api_token.confirmation_description',
                                                )
                                            }}
                                        </DialogDescription>
                                    </DialogHeader>

                                    <DialogFooter class="gap-2">
                                        <DialogClose as-child>
                                            <Button variant="secondary">
                                                {{
                                                    t(
                                                        'account.api_token.button.cancel',
                                                    )
                                                }}
                                            </Button>
                                        </DialogClose>

                                        <Button
                                            type="submit"
                                            variant="destructive"
                                            :disabled="processing"
                                            :data-test="`api-token-confirm-revoke-${token.id}`"
                                        >
                                            {{
                                                t(
                                                    'account.api_token.button.revoke',
                                                )
                                            }}
                                        </Button>
                                    </DialogFooter>
                                </Form>
                            </DialogContent>
                        </Dialog>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>
