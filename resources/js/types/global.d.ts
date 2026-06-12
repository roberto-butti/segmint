import type { Auth } from '@/types/auth';
import type { NavigationContext } from '@/types/navigation';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(
            pattern: string,
            options?: { eager?: boolean },
        ) => Record<string, T>;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            flash: {
                success: string | null;
                segmentCopy: {
                    id: string;
                    message: string;
                    destination_name: string;
                    destination_url: string;
                } | null;
                ruleTemplateCopy: {
                    id: string;
                    message: string;
                    destination_name: string;
                    destination_url: string;
                } | null;
            };
            navigationContext: NavigationContext;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
