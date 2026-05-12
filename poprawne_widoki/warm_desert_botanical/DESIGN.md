---
name: Warm Desert Botanical
colors:
  surface: '#f0ffd8'
  surface-dim: '#cee0b5'
  surface-bright: '#f0ffd8'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#e8facd'
  surface-container: '#e2f4c8'
  surface-container-high: '#dcefc3'
  surface-container-highest: '#d7e9bd'
  on-surface: '#121f05'
  on-surface-variant: '#544438'
  inverse-surface: '#273517'
  inverse-on-surface: '#e5f7cb'
  outline: '#877366'
  outline-variant: '#d9c2b3'
  surface-tint: '#924c00'
  primary: '#8f4a00'
  on-primary: '#ffffff'
  primary-container: '#ae611a'
  on-primary-container: '#fffbff'
  inverse-primary: '#ffb781'
  secondary: '#835418'
  on-secondary: '#ffffff'
  secondary-container: '#fdbd77'
  on-secondary-container: '#784a0d'
  tertiary: '#55612e'
  on-tertiary: '#ffffff'
  tertiary-container: '#6e7a44'
  on-tertiary-container: '#fcffe3'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#ffdcc4'
  primary-fixed-dim: '#ffb781'
  on-primary-fixed: '#2f1400'
  on-primary-fixed-variant: '#6f3800'
  secondary-fixed: '#ffdcbb'
  secondary-fixed-dim: '#faba75'
  on-secondary-fixed: '#2b1700'
  on-secondary-fixed-variant: '#673d00'
  tertiary-fixed: '#dbe9a9'
  tertiary-fixed-dim: '#bfcd8f'
  on-tertiary-fixed: '#171e00'
  on-tertiary-fixed-variant: '#404b1b'
  background: '#f0ffd8'
  on-background: '#121f05'
  surface-variant: '#d7e9bd'
typography:
  display:
    fontFamily: Plus Jakarta Sans
    fontSize: 48px
    fontWeight: '700'
    lineHeight: '1.1'
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 32px
    fontWeight: '700'
    lineHeight: '1.2'
  headline-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 28px
    fontWeight: '700'
    lineHeight: '1.2'
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '600'
    lineHeight: '1.3'
  body-lg:
    fontFamily: Manrope
    fontSize: 18px
    fontWeight: '400'
    lineHeight: '1.6'
  body-md:
    fontFamily: Manrope
    fontSize: 16px
    fontWeight: '400'
    lineHeight: '1.6'
  label-md:
    fontFamily: Manrope
    fontSize: 14px
    fontWeight: '600'
    lineHeight: '1.4'
    letterSpacing: 0.01em
  label-sm:
    fontFamily: Manrope
    fontSize: 12px
    fontWeight: '700'
    lineHeight: '1.4'
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  unit: 8px
  container-max: 1280px
  gutter: 24px
  margin-mobile: 16px
  margin-desktop: 40px
---

## Brand & Style

This design system is built to evoke the serene, resilient, and life-affirming atmosphere of a desert oasis. It balances the precision of a digital dashboard with the organic warmth of a physical plant journal. The aesthetic is "Modern Tactile"—it avoids the sterile, high-gloss finish of typical SaaS products in favor of soft textures, earthy pigments, and friendly geometries.

The target audience consists of hobbyist gardeners and houseplant enthusiasts who value both functionality and emotional connection to their greenery. The UI should feel like a trusted companion: encouraging, clear, and grounded.

**Visual Pillars:**
- **Warmth over Neutrality:** Every surface uses cream or sand tones rather than pure whites or grays.
- **Organic Precision:** Lines are clean and layouts are structured, but corners are deeply rounded to mimic the softening effect of wind-swept sand.
- **Resilient Growth:** The use of Terracotta and Forest Green represents the intersection of human care and natural vitality.

## Colors

The palette is derived from natural earth tones found in arid botanical environments. 

- **Primary (Terracotta):** Reserved for primary actions, critical plant needs (like urgent watering), and brand highlights.
- **Secondary (Sand):** Used for secondary buttons, subtle dividers, and decorative backgrounds.
- **Background (Cream):** The primary canvas color. It reduces eye strain compared to pure white and reinforces the "paper journal" feel.
- **Text (Forest Green):** Provides high-contrast legibility while remaining softer and more organic than black or slate.
- **Accent (Olive Green):** Used for "healthy" status indicators, growth tracking, and success states.

**Functional Color Application:**
- **Status Badges:** Use low-opacity tints of Terracotta (alert) or Olive (healthy) with full-saturation text.
- **Surface Layering:** While the background is Cream, cards and containers can utilize pure White (#ffffff) to create subtle lift.

## Typography

This design system employs a pairing of **Plus Jakarta Sans** for expressive, friendly headers and **Manrope** for highly readable, functional body text.

- **Plus Jakarta Sans:** Selected for its optimistic, slightly rounded terminals that complement the "friendly" brand personality. It should be used for all brand-level headings and card titles.
- **Manrope:** A workhorse typeface that maintains clarity in data-heavy views like plant statistics and admin tables.

**Type Hierarchy Guidance:**
- Use `display` for empty state titles and major dashboard welcomes.
- Use `label-sm` with all-caps for metadata, such as "LAST WATERED" or "LATIN NAME."
- Maintain a generous line-height (1.6) for body text to keep the "journal" entries feeling airy and easy to scan.

## Layout & Spacing

The design system follows a strict 8px rhythm to ensure visual harmony across components. 

**Grid Strategy:**
- **Desktop:** 12-column fixed grid with a 1280px max-width. Content is centered. The sidebar is fixed at 280px and does not count towards the 12-column content area.
- **Tablet:** 8-column fluid grid.
- **Mobile:** 4-column fluid grid with 16px side margins.

**Rhythm:**
- **Card Padding:** Standardized at 24px (3 units) for internal content.
- **Section Spacing:** 48px to 64px (6-8 units) between major vertical sections to allow the layout to "breathe," reinforcing the minimalist desert aesthetic.

## Elevation & Depth

To avoid a cold, corporate feel, this design system eschews harsh black shadows in favor of "Desert Shadows"—soft, diffused elevations tinted with the Forest Green text color.

**Elevation Levels:**
- **Level 0 (Flat):** The Cream background.
- **Level 1 (Surface):** Default state for cards and inputs. A very soft, wide blur (16px) with 4-6% opacity using `#283618`.
- **Level 2 (Hover/Active):** Increased depth (24px blur) with 8% opacity. Used when a user interacts with a plant or task card.
- **Level 3 (Overlay):** Used for modals and floating action buttons. Includes a subtle backdrop blur (4px) to create a "frosted glass on sand" effect.

Depth is also conveyed through color stacking: The secondary (Sand) color can be used as a flat, non-elevated background for specific sections to create "wells" of content without using shadows.

## Shapes

The shape language is defined by significant rounding to evoke organic forms like leaves, pebbles, and dunes.

- **Standard Components:** Buttons, inputs, and small chips use `rounded-lg` (16px).
- **Cards:** Main plant and task cards use `rounded-xl` (24px) to stand out as distinct, touchable objects.
- **Navigation:** The active state indicator in the sidebar and the bottom nav bar itself should use "pill" shapes (full rounding) for a playful, modern touch.
- **Progress Bars:** Fully rounded ends (pill-shaped) to keep the data visualization feeling friendly rather than clinical.

## Components

### Logo & Iconography
- **Logo:** A minimal flat vector mark. The camel silhouette should be stylized with soft curves, its hump transitioning smoothly into the canopy of a broad-leafed tree.
- **Care Icons:** Simple line icons (2px stroke) in Forest Green. Watering cans, suns, and fertilizer drops should have slightly rounded corners to match the typography.

### Navigation
- **Sidebar (Desktop):** A vertical rail on the left. The active item is highlighted by a Terracotta pill-shaped background with White text.
- **Bottom Nav (Mobile):** A floating curved bar at the bottom of the screen with a slight backdrop blur. Icons use a "labeled" style for accessibility.

### Cards
- **Plant Cards:** Feature a large image with a `rounded-lg` clip at the top. The bottom section contains the plant name in `headline-md` and small care icons with status indicators.
- **Task Cards:** Compact, horizontal layout. A checkbox on the left (Forest Green border), the task name, and a "Due" badge on the right.

### Inputs & Admin
- **Form Fields:** Thick 2px borders in Sand, turning Terracotta on focus. Backgrounds are pure White for maximum contrast.
- **Tables:** No vertical lines. Horizontal dividers are 1px Sand. Header row is Forest Green with `label-sm` typography.

### Data & Feedback
- **Status Badges:** Pill-shaped with a light tint background (e.g., light Olive for "Healthy").
- **Charts:** Use smooth splines for line charts rather than jagged lines. Bars should have rounded tops. Use the primary palette (Terracotta, Olive, Sand) for data series.
- **Empty States:** Centered illustrations featuring a friendly camel resting under a stylized tree, accompanied by a `display` heading and a Primary Action button.