import type { InertiaLinkProps } from '@inertiajs/vue3';
import { translate } from '@/composables/useTranslations';
import type { BreadcrumbItem } from '@/types';

/**
 * The layout props every page receives, whatever else it also gets.
 *
 * A page whose breadcrumbs depend on its own data widens this with an
 * intersection and passes the wider type as `P`.
 */
export type BreadcrumbLayoutProps = {
    locale: string;
    fallbackLocale: string;
};

/**
 * One crumb, labelled either by translation key or by a resolved title.
 *
 * A key stays unresolved here because a page's layout function runs outside a
 * component, where `useTranslations` has no page to read the locale from. A
 * title is for a crumb naming a record rather than a screen: the document a
 * reader opened is called whatever it is called, in every locale.
 */
export type BreadcrumbCrumb = {
    href: NonNullable<InertiaLinkProps['href']>;
} & ({ key: string } | { title: string });

/**
 * Build a page's layout function from its breadcrumb trail.
 *
 * Takes a callback rather than a plain array so a page can derive its trail
 * from its own layout props; three pages do. Pages with a fixed trail ignore
 * the argument.
 */
export const breadcrumbLayout =
    <P extends BreadcrumbLayoutProps = BreadcrumbLayoutProps>(
        crumbs: (props: P) => BreadcrumbCrumb[],
    ) =>
    (props: P): { breadcrumbs: BreadcrumbItem[] } => ({
        breadcrumbs: crumbs(props).map((crumb) => ({
            title:
                'key' in crumb
                    ? translate(crumb.key, props.locale, props.fallbackLocale)
                    : crumb.title,
            href: crumb.href,
        })),
    });
