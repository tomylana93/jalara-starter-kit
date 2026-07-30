# Color Theme

- Official presets: `neutral`, `blue`, `emerald`, `violet`, `rose`, `amber`, `teal`, `cyan`, `indigo`, and `orange`; keep the server enum, localized labels, TypeScript union, and `colorThemePresets` synchronized.
- Each preset aliases the Tailwind `50`–`950` ramp in `resources/css/app.css`; one shared mapping changes only brand accents: `primary`, `ring`, all chart tokens, `sidebar-primary`, and `sidebar-ring`, including their related foreground tokens.
- A color theme is a brand accent, not full chrome. `background`, `card`, `popover`, `secondary`, `muted`, `accent`, `border`, `input`, and base sidebar surfaces remain neutral. Destructive and validation/success status colors also remain independent of the brand hue.
- Light mode uses a dark primary with a light foreground; dark mode uses a light primary with a dark foreground. Dark selectors must support both root `.dark[data-color-theme]` and nested preview `.dark [data-color-theme]` contexts.
- `<html data-color-theme>` owns the persisted global theme. Branding drafts use local `data-color-theme` attributes on Identity, authentication layout, application layout, and swatch previews; changing a draft must not mutate `<html>` before a successful save.
- Chrome applies the brand to primary actions, focus rings, active navigation indicators, avatar fallbacks, the Inertia progress indicator, charts, and sidebar primary elements. Auth Simple/Card use neutral canvases; Auth Split uses a neutral black base/overlay with white identity text so its image tint does not follow the brand preset.
