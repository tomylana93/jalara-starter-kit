export type AuthLayoutPreset = 'simple' | 'card' | 'split';
export type AppLayoutPreset = 'sidebar' | 'header';
export type ColorThemePreset =
    'neutral' | 'blue' | 'emerald' | 'violet' | 'rose' | 'amber';
export type FontPreset =
    'instrument-sans' | 'system-sans' | 'system-serif' | 'system-mono';

export type Branding = {
    companyName: string;
    footerText: string | null;
    authLayout: AuthLayoutPreset;
    appLayout: AppLayoutPreset;
    colorTheme: ColorThemePreset;
    fontPreset: FontPreset;
};
