---
name: Academic Pulse
colors:
  surface: '#f8f9fc'
  surface-dim: '#d9dadd'
  surface-bright: '#f8f9fc'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f2f3f6'
  surface-container: '#edeef1'
  surface-container-high: '#e7e8eb'
  surface-container-highest: '#e1e2e5'
  on-surface: '#191c1e'
  on-surface-variant: '#5a413b'
  inverse-surface: '#2e3133'
  inverse-on-surface: '#f0f1f4'
  outline: '#8f7069'
  outline-variant: '#e3beb6'
  surface-tint: '#b42806'
  primary: '#b42806'
  on-primary: '#ffffff'
  primary-container: '#ff5d39'
  on-primary-container: '#5c0d00'
  inverse-primary: '#ffb4a3'
  secondary: '#5d5e62'
  on-secondary: '#ffffff'
  secondary-container: '#dfdfe3'
  on-secondary-container: '#616266'
  tertiary: '#0053db'
  on-tertiary: '#ffffff'
  tertiary-container: '#668eff'
  on-tertiary-container: '#00266f'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#ffdad2'
  primary-fixed-dim: '#ffb4a3'
  on-primary-fixed: '#3d0600'
  on-primary-fixed-variant: '#8b1900'
  secondary-fixed: '#e2e2e6'
  secondary-fixed-dim: '#c6c6ca'
  on-secondary-fixed: '#1a1c1f'
  on-secondary-fixed-variant: '#45474a'
  tertiary-fixed: '#dbe1ff'
  tertiary-fixed-dim: '#b4c5ff'
  on-tertiary-fixed: '#00174b'
  on-tertiary-fixed-variant: '#003ea8'
  background: '#f8f9fc'
  on-background: '#191c1e'
  surface-variant: '#e1e2e5'
typography:
  headline-xl:
    fontFamily: Plus Jakarta Sans
    fontSize: 36px
    fontWeight: '700'
    lineHeight: 44px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 28px
    fontWeight: '700'
    lineHeight: 36px
    letterSpacing: -0.02em
  headline-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 22px
    fontWeight: '700'
    lineHeight: 28px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
    letterSpacing: -0.01em
  title-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 24px
  title-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 15px
    fontWeight: '600'
    lineHeight: 20px
  body-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  body-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 13px
    fontWeight: '400'
    lineHeight: 18px
  label-md:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.02em
  label-sm:
    fontFamily: Inter
    fontSize: 11px
    fontWeight: '600'
    lineHeight: 14px
    letterSpacing: 0.03em
rounded:
  sm: 0.5rem
  DEFAULT: 1rem
  md: 1.5rem
  lg: 2rem
  xl: 3rem
  full: 9999px
spacing:
  gutter: 1.25rem
  margin: 2rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2rem
---

## Brand & Style

This design system delivers an executive-grade, fluid academic workspace for managing thesis defense workflows, proposals, and advisory schedules (Sistem Informasi Tugas Akhir dan Skripsi). It balances institutional rigor with modern SaaS agility, departing from bureaucratic academic portals in favor of clarity, progress momentum, and low cognitive friction.

### Target Audience
- **Undergraduate & Graduate Students:** Tracking proposal reviews, defense schedules, revision iterations, and advisor approvals.
- **Academic Advisors & Examiners:** Reviewing manuscripts, scoring defenses, leaving granular feedback, and managing quota capacities.
- **Department Coordinators:** Auditing graduation pipelines, assigning defense panels, and compiling cohort progress metrics.

### Visual Style
A blend of **Modern Minimalist SaaS** and **Tactile Soft Surfaces**, directly informed by clean fintech dashboards:
- Pristine, warm-neutral canvas backgrounds (`#F4F5F8`) contrasting against pure white container blocks.
- High-contrast typography anchoring numerical progress metrics and student identifiers.
- Energetic coral-orange hero highlights to communicate urgency, action items, and live milestones.
- Pill-shaped interaction controls, soft micro-elevations, and refined low-contrast borders.

## Colors

The palette leverages high-contrast functional color-blocking. Pure white containers float atop an ultra-soft cool-slate background, accented by a commanding primary coral accent and obsidian charcoal ink.

### Functional Palette
- **Canvas Base:** `#F4F5F8` (Soft tint background reducing eye fatigue during intensive reading)
- **Surface Layer 1 (Cards & Modals):** `#FFFFFF`
- **Surface Layer 2 (Inner Wells, Pill Badges, Table Rows):** `#F8F9FB`
- **Primary Accent:** `#FF5D39` (Action buttons, primary active tab pills, hero KPI cards, urgent prompts)
- **Primary Ink (Text / Main Brand):** `#121417` (High readability, obsidian ink)
- **Secondary Ink (Muted / Subtitles):** `#64748B`
- **Border / Divider Outline:** `#E9EBEF`

### Academic Workflow Status Accents
- **Approved / Completed:** Surface `#ECFDF5`, Border `#A7F3D0`, Text `#059669`
- **In Progress / Under Review:** Surface `#FFFBEB`, Border `#FDE68A`, Text `#D97706`
- **Revisions Required / Action Needed:** Surface `#FFF1F0`, Border `#FECDD3`, Text `#E11D48`
- **Pending / Scheduled:** Surface `#EFF6FF`, Border `#BFDBFE`, Text `#2563EB`

## Typography

The type system is engineered around **Plus Jakarta Sans** for expressive, human-friendly titles and clear metric readouts, paired with **Inter** for micro-data, system timestamps, and tabular metadata.

- **KPI Metric Numbers:** Rendered in `headline-xl` or `headline-lg` with tight negative tracking (`-0.02em`) to ensure bold visual weight similar to financial balances.
- **Section Headers & Titles:** Kept clean and unornamented. Title sub-labels sit directly beneath in `#64748B` with `body-sm`.
- **Status Tags & Action Badges:** Styled strictly with `label-md` or `label-sm` in Inter to maximize legibility at compact scales across data tables.

## Layout & Spacing

The layout is built on a responsive 12-column fluid grid framed within a constrained desktop container (`max-width: 1440px`), surrounded by a fixed navigation spine and top toolbar.

### Shell Architecture
- **Desktop (1024px+):** Fixed icon-rail navigation on the left (`72px` width) with floating cards separated by `gutter: 1.25rem` (20px). The canvas margins maintain `margin: 2rem` (32px).
- **Tablet (768px – 1023px):** 8-column layout. Sidebar collapses into an overlay drawer. Gutter shifts to `1rem` (16px) and canvas margin to `1.5rem` (24px).
- **Mobile (< 768px):** Single-column stacked cards. Navigation moves to a fixed bottom pill bar or sticky top bar. Margin shrinks to `1rem` (16px) with vertical gaps of `1rem`.

### Spacing Principles
- Component inner padding scales with surface importance: stat micro-cards use `space-lg` (24px), while table cells and compact pills use `space-xs` through `space-md`.
- Group related metadata using `space-xs` (4px) to `space-sm` (8px); separate independent sections using `space-xl` (32px).

## Elevation & Depth

This design system uses delicate, ambient drop shadows combined with hairline borders (`#E9EBEF`) rather than high-contrast structural outlines.

### Depth Hierarchy
1. **Canvas Base (`#F4F5F8`):** Flat foundation level.
2. **Standard Card Tier (Elevation 1):**
   - Background: `#FFFFFF`
   - Border: `1px solid rgba(233, 235, 239, 0.8)`
   - Shadow: `0px 4px 20px -2px rgba(18, 20, 23, 0.04), 0px 2px 6px -1px rgba(18, 20, 23, 0.02)`
3. **Hero / Stat Card Tier (Elevation 2 - Accent & Dark Cards):**
   - Solid fill (`#FF5D39` or `#121417`)
   - Shadow: `0px 10px 25px -4px rgba(255, 93, 57, 0.25)` for primary coral; `0px 10px 25px -4px rgba(18, 20, 23, 0.15)` for dark obsidian cards.
4. **Floating Floating Overlays & Dropdowns (Elevation 3):**
   - Shadow: `0px 16px 36px -6px rgba(18, 20, 23, 0.10), 0px 4px 12px -2px rgba(18, 20, 23, 0.05)`
   - Backdrop filter on sticky elements: `blur(12px) rgba(255, 255, 255, 0.85)`

## Shapes

The system adopts a friendly, pill-forward curvature language (`roundedness: 3`).

- **Dashboard Outer Containers & Major Cards:** Rounded using `1.5rem` to `1.75rem` (24px–28px) creating calm, pebble-like sections.
- **Inner Data Pods & Stat Tiles:** Set to `1rem` (16px) or `1.25rem` (20px).
- **Interactive Controls (Buttons, Search Inputs, Segmented Tabs, Filter Triggers):** Full pill (`9999px` / `rounded-full`).
- **Status Tags & Trend Badges:** Rounded pills (`9999px`) with generous horizontal padding to retain structural balance alongside square table data.

## Components

### Buttons
- **Primary Pill:** `#FF5D39` solid background, white text, pill-shaped (`rounded-full`), padded `10px 20px`. Hover brings subtle brightness scaling and an ambient orange shadow glow.
- **Secondary Pill:** `#121417` solid background, white text. Used for main transactional actions (e.g., "Submit Defense Draft", "Approve Proposal").
- **Ghost / Neutral Pill:** Pure white background `#FFFFFF`, border `1px solid #E9EBEF`, text `#121417`. Used for secondary actions (e.g., "Export Log", "Request Extension").

### KPI Stat Cards
- **Featured Accent Card:** Solid `#FF5D39` background with white text, housing critical real-time alerts (e.g., "Defense Ready: 4 Days Remaining" or "Pending Approvals: 5").
- **Standard Stat Card:** White container, subtle border `#E9EBEF`. Top label (`body-sm`, `#64748B`), large primary metric (`headline-lg`, `#121417`), paired with an inline pill badge indicating cohort trends (e.g., `↑ 12% from last cycle` with green `#10B981` text and dot).

### Status Pills
- Applied within table rows and submission headers:
  - **Completed / Approved:** `#ECFDF5` background, `#059669` text, accompanied by a `6px` solid emerald dot indicator.
  - **In Progress:** `#FFFBEB` background, `#D97706` text, accompanied by an amber dot.
  - **Pending / Scheduled:** `#EFF6FF` background, `#2563EB` text, accompanied by a blue dot.
  - **Revisions Required:** `#FFF1F0` background, `#E11D48` text, accompanied by a rose dot.

### Navigation & Segmented Tabs
- **Segmented Control Bar:** Enclosed in a floating white or soft grey well (`#F0F2F5`), items render as rounded pills. Active state is filled with solid `#121417` and crisp white typography; inactive tabs display `#64748B` without background.
- **Icon Rail Navigation:** Vertical icon strip with circular or softly rounded icon buttons. Active tab sits inside a solid `#121417` badge with an inverted white icon.

### Form Inputs & Search Fields
- Fully rounded pills (`rounded-full`), `#FFFFFF` background, inset border `#E9EBEF`.
- Prefixed with search or filter vector glyphs in `#94A3B8`. Focus state applies a crisp `1.5px` stroke of `#FF5D39` with no heavy outline ring.

### Data Tables
- Clean borderless cells on alternating `#FFFFFF` and `#FAFAFC` rows.
- Left-aligned academic metadata (Student Name, NIM, Thesis Title), center-aligned status pills, and right-aligned action trigger menus (`•••` pill button).