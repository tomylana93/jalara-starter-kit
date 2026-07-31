<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogo from '@/components/AppLogo.vue';
import AppMobileNavigation from '@/components/AppMobileNavigation.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useAppNavigation } from '@/composables/useAppNavigation';
import { dashboard } from '@/routes';

const { isMobile, setOpenMobile } = useSidebar();
const { mainGroups, footerItems } = useAppNavigation();
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <AppMobileNavigation v-if="isMobile" @close="setOpenMobile(false)" />

        <template v-else>
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" as-child>
                            <Link :href="dashboard()">
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain
                    v-for="group in mainGroups"
                    :key="group.title"
                    :group="group.title"
                    :items="group.items"
                />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter :items="footerItems" />
                <NavUser />
            </SidebarFooter>
        </template>
    </Sidebar>
    <slot />
</template>
