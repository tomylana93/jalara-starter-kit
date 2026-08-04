import type { InertiaLinkProps } from '@inertiajs/vue3';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

/**
 * Reports whether keyboard hints should name ⌘ rather than Ctrl. The user agent
 * is read instead of the deprecated `navigator.platform`, and iPadOS is treated
 * as Apple even though it reports itself as a Macintosh.
 */
export function isApplePlatform(): boolean {
    return (
        typeof navigator !== 'undefined' &&
        /Mac|iPhone|iPad|iPod/i.test(navigator.userAgent)
    );
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}
