<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { TriangleAlert } from '@lucide/vue';
import { useTemplateRef } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Account/ProfileController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
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
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';

const passwordInput = useTemplateRef('passwordInput');
const { t } = useTranslations();
</script>

<template>
    <div class="space-y-6">
        <Heading
            variant="small"
            :title="t('account.profile.disable.title')"
            :description="t('account.profile.disable.description')"
        />
        <div class="space-y-4">
            <Alert variant="destructive">
                <TriangleAlert />
                <AlertTitle>
                    {{ t('account.profile.disable.label.warning') }}
                </AlertTitle>
                <AlertDescription>
                    {{ t('account.profile.disable.warning') }}
                </AlertDescription>
            </Alert>
            <Dialog>
                <DialogTrigger as-child>
                    <Button
                        variant="destructive"
                        data-test="disable-user-button"
                    >
                        {{ t('account.profile.disable.button.disable') }}
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="ProfileController.disable.form()"
                        reset-on-success
                        @error="() => passwordInput?.focus()"
                        :options="{
                            preserveScroll: true,
                        }"
                        class="space-y-6"
                        v-slot="{ errors, processing, reset, clearErrors }"
                    >
                        <DialogHeader class="space-y-3">
                            <DialogTitle>
                                {{
                                    t(
                                        'account.profile.disable.confirmation_title',
                                    )
                                }}
                            </DialogTitle>
                            <DialogDescription>
                                {{
                                    t(
                                        'account.profile.disable.confirmation_description',
                                    )
                                }}
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="password" class="sr-only">
                                {{
                                    t('account.profile.disable.label.password')
                                }}
                            </Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                ref="passwordInput"
                                :aria-invalid="Boolean(errors.password)"
                                :placeholder="
                                    t(
                                        'account.profile.disable.placeholder.password',
                                    )
                                "
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button
                                    variant="secondary"
                                    @click="
                                        () => {
                                            clearErrors();
                                            reset();
                                        }
                                    "
                                >
                                    {{
                                        t(
                                            'account.profile.disable.button.cancel',
                                        )
                                    }}
                                </Button>
                            </DialogClose>

                            <Button
                                type="submit"
                                variant="destructive"
                                :disabled="processing"
                                data-test="confirm-disable-user-button"
                            >
                                {{
                                    t('account.profile.disable.button.disable')
                                }}
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
