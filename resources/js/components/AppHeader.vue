<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Menu, Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import AppearanceToggle from '@/components/AppearanceToggle.vue';
import AppLogo from '@/components/AppLogo.vue';
import AppMobileNavigation from '@/components/AppMobileNavigation.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import GlobalSearchTrigger from '@/components/GlobalSearchTrigger.vue';
import NotificationBell from '@/components/NotificationBell.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    NavigationMenu,
    NavigationMenuContent,
    NavigationMenuItem,
    NavigationMenuLink,
    NavigationMenuList,
    NavigationMenuTrigger,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import UserMenuContent from '@/components/UserMenuContent.vue';
import type { AppNavigationGroup } from '@/composables/useAppNavigation';
import { useAppNavigation } from '@/composables/useAppNavigation';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { getInitials } from '@/composables/useInitials';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const auth = computed(() => page.props.auth);
const mobileNavigationOpen = ref(false);
const { mainGroups, footerItems } = useAppNavigation();
const { isCurrentUrl, whenCurrentUrl } = useCurrentUrl();
const { t } = useTranslations();

const activeItemStyles = 'bg-accent text-accent-foreground';
const isGroupActive = (group: AppNavigationGroup): boolean =>
    group.items.some((item) => isCurrentUrl(item.href));
const openGlobalSearch = (): void => {
    window.dispatchEvent(new CustomEvent('open-global-search'));
};
</script>

<template>
    <div>
        <div class="border-b border-sidebar-border/80">
            <div class="mx-auto flex h-16 items-center px-4 md:max-w-7xl">
                <!-- Mobile Menu -->
                <div class="lg:hidden">
                    <Sheet v-model:open="mobileNavigationOpen">
                        <SheetTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="mr-2 h-9 w-9"
                            >
                                <Menu class="h-5 w-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent
                            side="left"
                            class="w-[18rem] bg-sidebar p-0 text-sidebar-foreground [&>button]:hidden"
                        >
                            <SheetTitle class="sr-only">
                                {{ t('navigation.menu.navigation') }}
                            </SheetTitle>
                            <SheetDescription class="sr-only">
                                {{ t('navigation.menu.description') }}
                            </SheetDescription>
                            <AppMobileNavigation
                                @close="mobileNavigationOpen = false"
                            />
                        </SheetContent>
                    </Sheet>
                </div>

                <Link :href="dashboard()" class="flex items-center gap-x-2">
                    <AppLogo />
                </Link>

                <!-- Desktop Menu -->
                <div class="hidden h-full lg:flex lg:flex-1">
                    <NavigationMenu
                        class="ml-10 flex h-full items-stretch"
                        data-test="desktop-navigation"
                    >
                        <NavigationMenuList
                            class="flex h-full items-stretch space-x-2"
                        >
                            <NavigationMenuItem
                                v-for="group in mainGroups"
                                :key="group.title"
                                class="relative flex h-full items-center"
                            >
                                <NavigationMenuTrigger
                                    data-test="desktop-navigation-group"
                                    :class="[
                                        isGroupActive(group)
                                            ? activeItemStyles
                                            : '',
                                        'h-9 cursor-pointer px-3',
                                    ]"
                                >
                                    {{ group.title }}
                                </NavigationMenuTrigger>
                                <NavigationMenuContent>
                                    <ul class="grid w-56 gap-1">
                                        <li
                                            v-for="item in group.items"
                                            :key="item.title"
                                        >
                                            <NavigationMenuLink
                                                as-child
                                                :class="[
                                                    whenCurrentUrl(
                                                        item.href,
                                                        activeItemStyles,
                                                    ),
                                                    'flex-row items-center gap-2',
                                                ]"
                                            >
                                                <Link :href="item.href">
                                                    <component
                                                        v-if="item.icon"
                                                        :is="item.icon"
                                                        class="size-4"
                                                    />
                                                    <span>{{
                                                        item.title
                                                    }}</span>
                                                </Link>
                                            </NavigationMenuLink>
                                        </li>
                                    </ul>
                                </NavigationMenuContent>
                                <div
                                    v-if="isGroupActive(group)"
                                    class="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-primary"
                                ></div>
                            </NavigationMenuItem>

                            <NavigationMenuItem
                                v-for="item in footerItems"
                                :key="item.title"
                                class="relative flex h-full items-center"
                            >
                                <NavigationMenuLink as-child>
                                    <Link
                                        data-test="desktop-navigation-link"
                                        :class="[
                                            navigationMenuTriggerStyle(),
                                            whenCurrentUrl(
                                                item.href,
                                                activeItemStyles,
                                            ),
                                            'h-9 cursor-pointer px-3',
                                        ]"
                                        :href="item.href"
                                    >
                                        <component
                                            v-if="item.icon"
                                            :is="item.icon"
                                            class="mr-2 h-4 w-4"
                                        />
                                        {{ item.title }}
                                    </Link>
                                </NavigationMenuLink>
                                <div
                                    v-if="isCurrentUrl(item.href)"
                                    class="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-primary"
                                ></div>
                            </NavigationMenuItem>
                        </NavigationMenuList>
                    </NavigationMenu>
                </div>

                <div class="ml-auto flex items-center space-x-2">
                    <div class="relative flex items-center space-x-1">
                        <GlobalSearchTrigger />
                        <Button
                            variant="ghost"
                            size="icon"
                            class="group h-9 w-9 cursor-pointer lg:hidden"
                            :aria-label="t('navigation.menu.search')"
                            data-test="global-search-trigger"
                            @click="openGlobalSearch"
                        >
                            <Search
                                class="size-5 opacity-80 group-hover:opacity-100"
                            />
                        </Button>
                    </div>

                    <NotificationBell />

                    <AppearanceToggle />

                    <div class="hidden lg:block">
                        <DropdownMenu>
                            <DropdownMenuTrigger :as-child="true">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="relative size-10 w-auto rounded-full p-1 focus-within:ring-2 focus-within:ring-primary"
                                >
                                    <Avatar
                                        class="size-8 overflow-hidden rounded-full"
                                    >
                                        <AvatarImage
                                            v-if="auth.user.avatar"
                                            :src="auth.user.avatar"
                                            :alt="auth.user.name"
                                        />
                                        <AvatarFallback
                                            class="rounded-lg bg-primary/10 font-semibold text-primary dark:bg-primary/15"
                                        >
                                            {{ getInitials(auth.user?.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-56">
                                <UserMenuContent :user="auth.user" />
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="props.breadcrumbs.length > 1"
            class="flex w-full border-b border-sidebar-border/70"
        >
            <div
                class="mx-auto flex h-12 w-full items-center justify-start px-4 text-muted-foreground md:max-w-7xl"
            >
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </div>
    </div>
</template>
