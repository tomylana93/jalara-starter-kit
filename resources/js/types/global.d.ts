import type { Primitive as PrimitiveComponent } from 'reka-ui';
import type { Abilities, Auth } from '@/types/auth';
import type { Branding } from '@/types/branding';
import type { ChatSharedState } from '@/types/chat';
import type { NotificationBellState } from '@/types/notifications';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            description: string | null;
            version: string;
            locale: string;
            fallbackLocale: string;
            auth: Auth;
            can: Abilities;
            branding: Branding;
            notificationBell: NotificationBellState;
            chat: ChatSharedState;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}

declare module 'vue' {
    interface GlobalComponents {
        Primitive: typeof PrimitiveComponent;
    }

    interface ComponentCustomProperties {
        $inertia: typeof Router;
        $page: Page;
        $headManager: ReturnType<typeof createHeadManager>;
    }
}
