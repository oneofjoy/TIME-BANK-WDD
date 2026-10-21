# TimeBank — Design System & UI Guidelines

**Version:** 1.0 (initial design specification)  
**Project:** TimeBank — Peer-to-Peer Skill Exchange  
**Frontend:** HTML5, CSS3, Vanilla JavaScript  
**Design direction:** **Minimalist Modern · Friendly · Interactive · Learning-Focused**

> This is the visual source of truth for the TimeBank frontend. Pair it with `AGENTS.md`, which governs project structure and development behavior. This document defines design intentions and reusable UI rules; it does not mean every component or page has already been implemented.

---

## 1. Product identity & design principles

**Core promise:** *Teach what you know. Learn what you love. Exchange time instead of money.*

TimeBank is an inclusive learning community where a user can **both learn and teach** through a single member account. Its core concept is **Teach 1 Hour → Earn 1 Time Credit → Learn 1 Hour → Repeat**. A credit is earned when a teaching session is successfully completed and verified, not merely when someone lists a skill.

### Design personality

- **Minimalist:** Clean composition, limited decorative elements, generous white space.
- **Modern:** Rounded shapes, confident typography, aligned grids, refined shadows.
- **Warm and approachable:** Soft aqua and cream backgrounds, natural illustrations, human language.
- **Playfully interactive:** Clear hover/focus feedback and small, purposeful animations.
- **Learning-first:** Visitors should understand the time-credit concept before seeing complex features.

### Non-negotiable principles

1. Clarity and usability take priority over effects.
2. Every visual element must support understanding, navigation, or an action.
3. Use consistent colors, fonts, radii, spacing, and component states.
4. Ensure accessible contrast, keyboard usability, and responsive layouts.
5. Do not suggest that prototype transactions, bookings, or authentication are real if no backend exists.

---

## 2. Color palette

The four **source colors** below come directly from the approved color-palette image provided for TimeBank. Additional neutrals and deeper shades are **support colors chosen for readability and UI states**, not replacements for the brand palette.

### 2.1 Primary brand palette

| Token | Color | Name | Intended usage |
|---|---|---|---|
| `--brand-aqua` | `#8AD6D1` | Light Aqua | Soft backgrounds, visual highlights, illustrations |
| `--brand-teal` | `#359FA0` | Teal | Brand identity, icons, strokes, selected details |
| `--brand-cream` | `#FFF0C5` | Warm Cream | Featured surfaces, navbar background, CTA accents |
| `--brand-coral` | `#FF8C52` | Coral Orange | Selected words, small accents, decorative emphasis |

### 2.2 Functional/supporting colors

| Token | Color | Usage |
|---|---|---|
| `--color-text` | `#12363D` | Primary text, headings |
| `--color-text-muted` | `#526A70` | Secondary copy, help text |
| `--color-primary-action` | `#176D70` | Primary buttons, accessible active states |
| `--color-primary-action-hover` | `#12595C` | Primary hover/pressed treatment |
| `--color-background` | `#FFFFFF` | Main canvas |
| `--color-surface-soft` | `#F5FAF9` | Alternating sections, cards, panels |
| `--color-border` | `#D9E6E4` | Dividers, borders, form inputs |
| `--color-focus` | `#176D70` | Visible keyboard focus ring |
| `--color-error` | `#B42318` | Form errors and destructive feedback |
| `--color-success` | `#176D70` | Confirmations and success emphasis |

**Accessibility note:** The brand teal `#359FA0` with white text is only about **3.17:1**, so do **not** use it for normal-sized white button text. Prefer `#176D70` for filled buttons with white text (about **6.07:1**), or dark text on light aqua/cream. White text on brand coral also fails normal-text contrast; use dark text instead.

### 2.3 Color usage rules

- Overall background: predominantly **white** or **soft off-white**.
- Use **cream** and **aqua** as large but restrained accent surfaces.
- Use **dark readable text** (`--color-text`) for most paragraphs and headings.
- Use **coral sparingly** for emphasized words, illustration accents, and small indicators—not large paragraphs.
- Do not put all four brand colors at full intensity into one small component.
- Avoid heavy multicolor gradients, neon effects, and overly saturated backgrounds.

### Suggested color distribution (guideline, not a strict ratio)

- 65–75% white / very light neutral
- 15–25% aqua / cream supporting surfaces
- 5–10% teal / coral highlights and actions

---

## 3. Typography

Use **two complementary, modern Google Fonts**:

- **Headings / display:** `Plus Jakarta Sans` — energetic, geometric, friendly, strong headings.
- **Body / UI / buttons:** `DM Sans` — clear at small sizes, comfortable for forms and navigation.

**Fallbacks:** `Arial`, `Helvetica`, `sans-serif`. When font loading fails, layout must remain usable. Avoid introducing other font families without a deliberate design change.

### 3.1 Typography scale

| Style | Desktop | Mobile | Weight | Line height | Font |
|---|---:|---:|---:|---:|---|
| Display / Hero H1 | 64 px | 36 px | 700–800 | 1.10–1.15 | Plus Jakarta Sans |
| H2 / Section heading | 44 px | 30 px | 700 | 1.15–1.20 | Plus Jakarta Sans |
| H3 / Subsection heading | 26 px | 22 px | 700 | 1.25–1.30 | Plus Jakarta Sans |
| H4 / Card heading | 20 px | 18 px | 600–700 | 1.30 | Plus Jakarta Sans |
| Lead / Intro paragraph | 20 px | 18 px | 400–500 | 1.55 | DM Sans |
| Body / Default paragraph | 16 px | 16 px | 400 | 1.65 | DM Sans |
| Body small / Supporting | 14 px | 14 px | 400–500 | 1.50 | DM Sans |
| Caption / Overline | 12–13 px | 12 px | 600–700 | 1.40 | DM Sans |
| Navbar | 14–15 px | 14–15 px | 500–600 | 1.40 | DM Sans |
| Buttons | 15–16 px | 15–16 px | 600–700 | 1.20 | DM Sans |
| Form labels | 14 px | 14 px | 600 | 1.40 | DM Sans |

Typography values between mobile and desktop should scale smoothly where appropriate (`clamp()`), not jump unnecessarily at every breakpoint.

### 3.2 Typography rules

- Use **one meaningful `<h1>` per standalone page**, followed by logical `<h2>`, `<h3>`, etc.
- Prefer sentence case over ALL CAPS for main copy. Small section labels may use uppercase with extra letter spacing.
- Keep hero headline around **6–10 words** when possible and limit desktop width to about **10–13 characters per line** or a similarly readable composition.
- Normal paragraph measure: **55–75 characters per line** (`max-width: 65ch` is a useful start).
- Use bold emphasis selectively; avoid more than two accent colors in one heading.
- Suggested heading letter spacing: `-0.035em` for H1, `-0.025em` for H2, `-0.015em` for H3.
- Do not set explanatory body text below 14 px; core body content should remain 16 px.

### 3.3 Font loading example

Place in each standalone page's `<head>` (or use a shared strategy appropriate to the existing code):

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
```

If fonts must work offline, use installed system-font fallbacks unless local licensed assets have been added intentionally. Do not commit commercial font files without permission.

---

## 4. Spacing, grid & layout

### 4.1 Spacing scale

Use a consistent **4px base scale**, concentrating on an 8px rhythm for larger components.

| Token | Pixels | Common usage |
|---|---:|---|
| `--space-1` | 4 | Tiny icon gap |
| `--space-2` | 8 | Small internal gaps |
| `--space-3` | 12 | Label/input details |
| `--space-4` | 16 | Card padding (compact) |
| `--space-6` | 24 | Standard card padding / column gap |
| `--space-8` | 32 | Component separation |
| `--space-12` | 48 | Group spacing |
| `--space-16` | 64 | Typical section gap (mobile/tablet) |
| `--space-24` | 96 | Large desktop section vertical padding |

### 4.2 Containers & grid

| Breakpoint | Viewport | Page gutter | Layout |
|---|---|---|---|
| Mobile | `< 640px` | 20 px | Single column; stacked components |
| Tablet | `640–1023px` | 32 px | 2-column layout when comfortable |
| Desktop | `>= 1024px` | 40–64 px | 12-column grid / split hero |

- Page content max-width: **1200px**; optional wide art may extend to **1280px**.
- Sections use `padding-block: 72–96px` on desktop, `56–72px` on tablet, `48–64px` on mobile.
- Section title to content gap: about **24–40px**.
- Cards in a grid: about **16–24px** gap.
- Never use fixed widths that break at 320–390px screen sizes.
- Use CSS Grid/Flexbox instead of pixel-positioning primary content.

### 4.3 Corners, outlines & shadows

- Small chips / inputs: **10–12px radius**.
- Content cards: **16–20px radius**.
- Large feature panels: **24–32px radius**.
- Navbar / pill buttons: **999px radius**.
- Borders: 1px solid `--color-border`; use subtle shadows, not heavy glows.
- Suggested card shadow: `0 6px 24px rgba(18, 54, 61, 0.06)`.
- Suggested floating navbar shadow: `0 10px 30px rgba(18, 54, 61, 0.10)`.

---

## 5. Shared component patterns

### 5.1 Floating persistent navbar

The Navbar is a shared component in `shared/navbar/` and appears across public pages.

**Public navigation:** `Home` · `Explore Skills` · `How It Works` · `Log In` · `Get Started`.

- **Desktop navbar height:** 72px; **tablet:** 64px; **mobile:** 60px.
- **Desktop top gap:** 20px; **mobile:** 12px.
- Width: responsive, with a **max-width near 1280px** and room on both sides.
- Shape: floating pill; background **cream or soft translucent cream**, with restrained blur and shadow.
- Left: TimeBank **wordmark**; center: public navigation (desktop); right: Log In + prominent Get Started.
- On mobile: wordmark, menu trigger, and a properly accessible expand/collapse menu if implemented.
- Must remain visible during scrolling without overlapping section content or obstructing anchors.
- Preserve the actual repository's chosen `sticky`/`fixed` implementation. Account for Navbar height/top offset in hero spacing and `scroll-padding-top`.
- Logo files should come from the existing `Assets/Logo/` folder using the real filename and correct page-relative path.

### 5.2 Buttons

| Variant | Treatment | Usage |
|---|---|---|
| Primary | Dark teal fill `#176D70`, white label | Explore Skills, Get Started, Join TimeBank |
| Secondary | Transparent/white, dark teal text and border | Offer a Skill, secondary CTA |
| Text | Text + arrow, no large fill | Learn more / section link |
| Disabled | Neutral fill and muted text | Actually unavailable action |

- Height: **44–48px desktop**; at least **44px touch target mobile**.
- Radius: **12px** for standard button or `999px` for pills; use one main style consistently.
- Horizontal padding: **20–28px**.
- Icon spacing: **8px**; icon should not change baseline alignment.
- Hover: slightly darken, small lift (`translateY(-2px)` maximum), subtle shadow.
- Focus: visible **2–3px** outline with a 2–3px offset; do not remove outline without replacing it.
- Loading, success, and disabled states must reflect real UI behavior; do not fake completed registrations or credits.

### 5.3 Skill/category cards

- White/light background, subtle border, 16–20px corner radius.
- Icon/illustration + category title + optional one-line supporting description + arrow.
- Entire card clickable when it actually navigates; prefer a semantic `<a>`.
- Hover: minor lift and subtle border/shadow emphasis. Avoid zooming so much that surrounding cards shift.
- Mobile: cards stack or use 2 columns **only if content remains readable**.

### 5.4 Forms (Login / Register)

- Clearly associated labels; placeholders provide examples, not replacements for labels.
- Input height around **48–52px**, radius **12px**, consistent inner padding.
- Provide distinct default / hover / focus / invalid / disabled states.
- Error text appears near the relevant field and is not conveyed by color alone.
- Password visibility toggle must be a real `<button type="button">` with accessible label.
- Forms should be easy to use on mobile; no tiny tap targets.
- Current authentication is a **frontend UI scope**; design must not imply backend security is already implemented.

### 5.5 Footer

- Keep simple: wordmark/logomark, concise description, useful links, and copyright.
- Use a calm surface (white/cream), subtle top border, generous padding.
- All footer links must point to actual available pages/sections; don't create dead-end navigation.

---

## 6. Homepage design specification — exactly four sections

**Required order:** Navbar → `Hero` → `About` → `Explore preview` → `CTA` → Footer. No additional FAQ, Gallery, Featured Tutors, or other sections unless requested.

### Section 01 — Hero (`pages/home/sections/hero/`)

**Goal:** Instantly explain the platform and offer two clear paths.

- Desktop: balanced **two-column** composition (copy left, illustration right).
- Mobile: single column; text and calls-to-action remain prominent.
- Eyebrow (optional): short category phrase such as `LEARN · TEACH · GROW TOGETHER`.
- **H1:** clear statement about exchanging skills through time rather than money.
- Supporting paragraph: 1–3 sentences, not a long block.
- Primary action: **Explore Skills**.
- Secondary action: **Offer a Skill** (route appropriately; if gated by auth, reflect that honestly).
- Illustration: vector/flat, people learning together; no stock-photo collage.
- White or airy aqua background; one coral-highlighted headline phrase at most.
- Do not put the floating Navbar on top of headline/controls.

### Section 02 — About TimeBank / Time Credit Cycle (`pages/home/sections/about/`)

**Goal:** Make the business model understandable at a glance.

- Heading suggestion: **“Learn Without Money. Share Through Time.”**
- Brief explanatory copy; avoid implying free unlimited credits.
- A **closed-loop** diagram with exactly three concepts:
  1. **Teach 1 Hour**
  2. **Earn 1 Time Credit**
  3. **Learn 1 Hour**
- The final arrow returns to the first step, showing continuous learning/teaching.
- Layout: left explanatory copy and right **circular/triangular loop** on desktop, stacked on mobile.
- Use different brand accent colors per step while retaining clear legibility.
- Decorative animation is optional; if used, keep it subtle and nonessential.
- **Business rule reminder:** time credit is granted after a teaching session is completed and verified; the diagram is a simplified concept, not a statement about immediate credit deposit.

### Section 03 — Explore Skills preview (`pages/home/sections/explore/`)

**Goal:** Let visitors discover what they could learn and continue to the full Explore page.

- Heading: **“What Do You Want to Learn?”**
- Display **6 example category cards** in a desktop **3 × 2** grid.
- Suggested categories: Programming, Design, Languages, Data Analysis, Business, Other Skills.
- Tablet: 2 columns; mobile: 1–2 columns based on available width.
- Include category icon, title, optional short descriptor, and directional cue.
- Clicking a card should link to the **Explore Skills page** with an appropriate category when that feature exists; otherwise use a meaningful general Explore destination.
- This is a **preview section**, not the standalone `pages/explore/` page.
- Do not invent real skill inventory, user counts, or ratings in the production UI.

### Section 04 — Final CTA (`pages/home/sections/cta/`)

**Goal:** Convert interest into starting the journey.

- Centered headline suggestion: **“Every Skill Has Value. Including Yours.”**
- Supporting line: **“Join a community where knowledge grows through sharing.”**
- Strong `Join TimeBank` / `Get Started` button leading to Register when the route exists.
- Use warm cream as the primary surface with restrained aqua/coral illustrations around the edges.
- Prioritize CTA readability and clear white space over decorative graphics.

---

## 7. Current standalone page guidance

### Explore Skills (`pages/explore/`)

- Purpose: browsable catalogue of learning categories and future offerings.
- Recommended hierarchy: page H1 → intro → search/filter controls → card grid → empty state.
- Search input / filters should have visible labels and selection states.
- Only display data and functionality that truly exist in the prototype; mock content should be clearly treated as sample UI.
- Keep card component appearance consistent with the Homepage Explore preview.

### Authentication (`pages/auth/login/` and `pages/auth/register/`)

- Shared visual language and form styles; simple focused layout with generous spacing.
- Keep signup/login clear, with helpful validation and a return path to Homepage.
- Do not show a successful real account creation without an actual backend.
- Consider a cream/aqua side panel for illustration on desktop; remove or simplify it on small screens.

### Future pages (not in current scope)

Skill Detail, Tutor Profile, Member Dashboard, Booking, Sessions, Schedule, Sync-Room, Wallet, Profile, and Admin are **future concepts**. Do not create design mock pages, markup, or routes for these automatically. When eventually introduced:

- Learner and Teacher use the **same member interface**.
- Admin uses a **separate admin interface**.
- Apply the same design foundations while prioritizing clarity for data-heavy dashboards.

---

## 8. Motion, interaction & visual engagement

TimeBank should feel interactive through **purposeful microinteractions**, not constant motion.

| Interaction | Suggested behavior |
|---|---|
| Buttons | Color shift + gentle `translateY(-2px)` on hover |
| Skill cards | Border/shadow emphasis; arrow shifts 2–4px |
| Navbar links | Underline or color indicator on hover/active |
| Navbar mobile menu | Smooth but quick expand/collapse, keyboard operable |
| About cycle | Optional step emphasis on hover/focus, arrows communicate direction even without motion |
| Section reveal | Optional short fade-up once in viewport, never required to access content |
| Forms | Immediate, clear focus and validation states |

- Microinteraction duration: **160–240ms** for common controls; **250–400ms** only for larger section reveal.
- Easing: `cubic-bezier(0.2, 0.8, 0.2, 1)` for smooth movement.
- Avoid bouncy UI, continuous spinning loops, parallax-heavy pages, scroll hijacking, or large animated backgrounds.
- All important content remains visible when JavaScript or animation support is absent.
- Respect `prefers-reduced-motion: reduce` and disable nonessential transitions/animations.

---

## 9. Responsive design & accessibility

### Responsive checks

- **320–359px:** verify logos, buttons, cycle steps, and card labels never clip.
- **360–639px:** mobile navbar/menu, stacked Hero, compact section gutters.
- **640–1023px:** flexible 2-column sections where possible; avoid squeezed copy.
- **1024px+:** full Navbar, two-column Hero/About, 3-column Explore grid.

### Accessibility checklist

- Text contrast **≥ 4.5:1** for normal text; **≥ 3:1** for sufficiently large/bold text and relevant controls.
- Tap targets should be at least **44 × 44px** where practical.
- Keyboard focus must be visible on links, buttons, fields, and menu controls.
- Meaningful `alt` text for images; decorative images use `alt=""`.
- Use proper `<a>` elements for navigation and `<button>` for actions.
- Do not rely solely on colors, icons, or motion to explain an action or the credit cycle.
- Avoid any keyboard trap in navigation dropdowns or dialogs.
- Preserve browser zoom and avoid horizontal scroll at mobile widths.

---

## 10. Design tokens starter for `css/global.css`

This is a **reference snippet**, not an instruction to overwrite working CSS. Merge with existing variables after checking the repository.

```css
:root {
  /* Approved brand palette */
  --brand-aqua: #8AD6D1;
  --brand-teal: #359FA0;
  --brand-cream: #FFF0C5;
  --brand-coral: #FF8C52;

  /* UI/support colors */
  --color-text: #12363D;
  --color-text-muted: #526A70;
  --color-primary-action: #176D70;
  --color-primary-action-hover: #12595C;
  --color-background: #FFFFFF;
  --color-surface-soft: #F5FAF9;
  --color-border: #D9E6E4;
  --color-focus: #176D70;
  --color-error: #B42318;
  --color-success: #176D70;

  --font-heading: 'Plus Jakarta Sans', Arial, sans-serif;
  --font-body: 'DM Sans', Arial, sans-serif;

  --text-h1: clamp(2.25rem, 4.5vw, 4rem);
  --text-h2: clamp(1.875rem, 3.3vw, 2.75rem);
  --text-h3: clamp(1.375rem, 2.2vw, 1.625rem);
  --text-h4: clamp(1.125rem, 1.6vw, 1.25rem);
  --text-lead: clamp(1.125rem, 1.6vw, 1.25rem);
  --text-body: 1rem;
  --text-small: 0.875rem;
  --text-caption: 0.75rem;

  --space-1: 4px;
  --space-2: 8px;
  --space-3: 12px;
  --space-4: 16px;
  --space-6: 24px;
  --space-8: 32px;
  --space-12: 48px;
  --space-16: 64px;
  --space-24: 96px;

  --radius-control: 12px;
  --radius-card: 18px;
  --radius-panel: 28px;
  --radius-pill: 999px;

  --shadow-card: 0 6px 24px rgba(18, 54, 61, 0.06);
  --shadow-navbar: 0 10px 30px rgba(18, 54, 61, 0.10);
  --layout-max-width: 1200px;

  --motion-fast: 180ms;
  --motion-normal: 240ms;
  --motion-ease: cubic-bezier(0.2, 0.8, 0.2, 1);
}

body {
  color: var(--color-text);
  background: var(--color-background);
  font-family: var(--font-body);
  font-size: var(--text-body);
  line-height: 1.65;
}

h1, h2, h3, h4 {
  font-family: var(--font-heading);
  line-height: 1.2;
}

h1 { font-size: var(--text-h1); line-height: 1.12; letter-spacing: -0.035em; }
h2 { font-size: var(--text-h2); letter-spacing: -0.025em; }
h3 { font-size: var(--text-h3); letter-spacing: -0.015em; }
h4 { font-size: var(--text-h4); }

:focus-visible {
  outline: 3px solid var(--color-focus);
  outline-offset: 3px;
}

@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
    scroll-behavior: auto !important;
  }
}
```

**Important:** `:root` defines reusable tokens only; component styling belongs in the relevant component CSS (`navbar.css`, `hero.css`, etc.). Avoid broad global selectors that break another section.

---

## 11. Asset usage and naming

- Keep existing capitalization and naming, especially `Assets/Logo/` and `Assets/Images/`.
- **Wordmark:** primary choice for public floating Navbar.
- **Logomark:** favicon/icon-sized use.
- **Primary combination mark:** use where adequate horizontal space is available.
- **Dark/monochrome variants:** use only against backgrounds with sufficient contrast.
- Avoid stretching logos; preserve aspect ratio and clear space.
- Illustration direction: flat/vector-like, friendly people and learning-related objects, modest detail, coordinated with the four brand colors.
- Optimize PNG/WebP assets and provide dimensions to reduce layout shift.
- Avoid adding unlicensed icons, photos, or external assets without permission.

---

## 12. Review checklist before shipping a UI change

- [ ] Matches the current **4-section Homepage**, or the explicitly requested Explore/Auth page.
- [ ] Uses the approved four brand colors and accessible support colors correctly.
- [ ] Uses Plus Jakarta Sans for headings and DM Sans for body/UI (or graceful fallbacks).
- [ ] Preserves floating-navbar positioning and visible gap before the Hero.
- [ ] Aligns to container, spacing, radius, button, and card rules.
- [ ] Works at **390px, 768px, and 1440px**, with no horizontal overflow.
- [ ] Has clear hover, focus, active, error, and disabled states where relevant.
- [ ] Respects reduced-motion and keyboard navigation.
- [ ] Shared components and page-specific styles remain separated.
- [ ] Does not link to future unimplemented routes or imply mock functionality is live.

---

## 13. Change management

1. Keep this file in the project root as `DESIGN.md`.
2. When changing visual standards, update tokens and relevant component guidance here.
3. If a concrete user request overrides a default in this file, follow the explicit request and update the document when that decision becomes permanent.
4. Use `AGENTS.md` for AI/development scope and `DESIGN.md` for visual decisions; do not treat planned pages as already implemented.

**Design objective:** A lightweight, credible, friendly learning platform that uses minimal visual complexity and meaningful interaction to make the **teach → earn → learn** cycle inviting and easy to understand.
