<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronsUpDown, X } from '@lucide/vue';
import { computed } from 'vue';
import BrandIdentity from '@/components/BrandIdentity.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useAppNavigation } from '@/composables/useAppNavigation';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useTranslations } from '@/composables/useTranslations';
import { toUrl } from '@/lib/utils';

const emit = defineEmits<{
    close: [];
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);
const { mainItems, externalItems } = useAppNavigation();
const { isCurrentUrl } = useCurrentUrl();
const { t } = useTranslations();
</script>

<template>
    <div
        data-test="app-mobile-navigation"
        class="flex h-full min-h-0 flex-col bg-sidebar text-sidebar-foreground"
    >
        <div
            class="flex h-16 shrink-0 items-center justify-between gap-3 border-b border-sidebar-border px-4"
        >
            <BrandIdentity />
            <Button
                variant="ghost"
                size="icon"
                class="size-8 shrink-0"
                :aria-label="t('navigation.menu.navigation')"
                data-test="mobile-navigation-close"
                @click="emit('close')"
            >
                <X class="size-4" />
            </Button>
        </div>

        <div class="flex min-h-0 flex-1 flex-col overflow-y-auto p-2">
            <nav
                class="flex flex-col gap-1"
                :aria-label="t('navigation.menu.navigation')"
            >
                <Link
                    v-for="item in mainItems"
                    :key="item.title"
                    :href="item.href"
                    class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                    :class="
                        isCurrentUrl(item.href)
                            ? 'bg-sidebar-accent text-sidebar-accent-foreground'
                            : ''
                    "
                    :aria-current="isCurrentUrl(item.href) ? 'page' : undefined"
                    @click="emit('close')"
                >
                    <component
                        v-if="item.icon"
                        :is="item.icon"
                        class="size-4"
                    />
                    <span>{{ item.title }}</span>
                </Link>
            </nav>

            <div class="mt-auto flex flex-col gap-1 pt-6">
                <a
                    v-for="item in externalItems"
                    :key="item.title"
                    :href="toUrl(item.href)"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-sidebar-foreground/70 transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                    @click="emit('close')"
                >
                    <component
                        v-if="item.icon"
                        :is="item.icon"
                        class="size-4"
                    />
                    <span>{{ item.title }}</span>
                </a>
            </div>
        </div>

        <div class="shrink-0 border-t border-sidebar-border p-2">
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button
                        variant="ghost"
                        class="h-auto w-full justify-start gap-2 px-2 py-2"
                        data-test="mobile-user-menu"
                    >
                        <UserInfo :user="user" />
                        <ChevronsUpDown class="ml-auto size-4 shrink-0" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    class="w-(--reka-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                    side="top"
                    align="end"
                    :side-offset="4"
                >
                    <UserMenuContent :user="user" @navigate="emit('close')" />
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </div>
</template>
