export type AuthLayoutPreset = 'simple' | 'card' | 'split';
export type AppLayoutPreset = 'sidebar' | 'header';
export type ColorThemePreset =
    | 'neutral'
    | 'blue'
    | 'emerald'
    | 'violet'
    | 'rose'
    | 'amber'
    | 'teal'
    | 'cyan'
    | 'indigo'
    | 'orange';
export type FontPairPreset =
    | 'instrument-sans'
    | 'space-grotesk-inter'
    | 'poppins-inter'
    | 'montserrat-open-sans'
    | 'playfair-display-source-sans';
export type BrandingIdentityMode = 'logo' | 'icon-text';
export type BrandingAsset =
    'logo' | 'logo-dark' | 'icon' | 'icon-dark' | 'auth-background';

export type Branding = {
    companyName: string;
    footerText: string | null;
    identityMode: BrandingIdentityMode;
    logoUrl: string | null;
    logoDarkUrl: string | null;
    iconUrl: string | null;
    iconDarkUrl: string | null;
    authBackgroundUrl: string | null;
    authLayout: AuthLayoutPreset;
    appLayout: AppLayoutPreset;
    colorTheme: ColorThemePreset;
    fontPair: FontPairPreset;
};
