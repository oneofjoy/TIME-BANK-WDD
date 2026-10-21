# AGENTS.md — TimeBank

## 1. Purpose & project context

This file guides AI coding assistants working on **TimeBank**, a small **IS556 Web Design and Development** academic project. Read this file and `DESIGN.md` before editing code. Inspect the actual repository first; the structure below is a reference, **not evidence that every file exists**.

**TimeBank concept:** Members exchange skills using time rather than money. The intended model is **1 hour taught = 1 Time Credit**, which may later be spent learning. **Learner and Teacher are activities within one Member account and interface**, not separate account types. Admin is a separate access role and a future UI scope.

**Project priority:** A clear, polished, functional academic demonstration. Keep the implementation simple and maintainable; do not introduce production-scale infrastructure or unrequested features.

## 2. Stack & local environment

- Frontend: **HTML5, CSS3, vanilla JavaScript (ES6+)**. No frontend framework, bundler, or unnecessary dependencies.
- Backend for database-connected features: **plain PHP + MySQL/MariaDB**, using XAMPP and phpMyAdmin locally.
- Keep standalone pages as `.html` where practical; communicate with PHP endpoints using `fetch()` and JSON. Do not convert all pages to `.php` without a concrete need.
- Test **PHP endpoints through Apache/XAMPP**, not VS Code Live Server alone; Live Server cannot execute PHP. Example local project URL: `http://localhost/TimeBank/` if installed under `C:\xampp\htdocs\TimeBank\`.
- Follow actual repository casing, especially `Assets/`, `Logo/`, and exact image filenames.
- No React, Vue, Tailwind, Node build system, ORM, API framework, or routing framework unless explicitly requested.

## 3. Current scope versus roadmap

### Current / authorized work

1. **Public Homepage** (`index.html`) with exactly four sections: Hero, About TimeBank, Explore preview, CTA.
2. **Shared Navbar and Footer** used on relevant public pages.
3. **Explore Skills page** (`pages/explore/`): skills catalogue UI; database-backed categories/skills may be wired as development progresses.
4. **Authentication pages** (`pages/auth/login/` and `pages/auth/register/`): minimalist UI, form validation, and integration with the existing MySQL schema via simple PHP endpoints when explicitly implementing auth.
5. **Database foundation:** SQL schema for `timebank_db` has been prepared and the user reports importing it. Verify actual schema and connection instead of assuming endpoints or tables work.

Do **not** infer that a page or PHP endpoint already exists simply because it is named here. Inspect the workspace and preserve work already completed.

### Future / not yet authorized for implementation

Skill Detail, Tutor Profile, Member Dashboard, Member Profile, Skill Offering management, Booking, Sessions, Sync-Room, Time Wallet, and **all Admin UI**. These describe the intended application direction, but **do not scaffold or implement them unless specifically requested**.

Do not build the entire Time Credit economy, booking engine, session management, or role-based admin screens while working on Login/Register.

## 4. Suggested lightweight project structure

```text
TimeBank/
├── AGENTS.md
├── DESIGN.md
├── README.md                     # if already present
├── index.html                    # Homepage
├── Assets/
│   ├── Logo/
│   ├── Images/
│   └── Icons/
├── shared/
│   ├── navbar/                  # navbar.html, navbar.css, navbar.js when needed
│   └── footer/                  # footer.html, footer.css
├── pages/
│   ├── home/
│   │   ├── home.js
│   │   ├── home.css              # if needed
│   │   └── sections/
│   │       ├── hero/             # hero.html + hero.css
│   │       ├── about/            # about.html + about.css (+ about.js if needed)
│   │       ├── explore/          # explore.html + explore.css
│   │       └── cta/              # cta.html + cta.css
│   ├── explore/                 # index.html + explore.css + explore.js
│   └── auth/
│       ├── login/               # index.html + login.css + login.js
│       └── register/            # index.html + register.css + register.js
├── php/                          # add only files needed by the current feature
│   ├── database.php             # PDO connection/config
│   ├── register.php             # planned endpoint
│   ├── login.php                # planned endpoint
│   ├── logout.php               # planned endpoint
│   └── get-skills.php           # planned catalogue endpoint
├── database/
│   └── timebank_database.sql    # schema/reference only
├── css/
│   └── global.css
└── js/
    └── include.js
```

This layout is intentionally **simple**: no separate `services/`, `middleware/`, `controllers/`, elaborate API architecture, or `.htaccess` requirement for a localhost mockup. If the existing repository differs, adapt to it rather than moving folders automatically.

**Important:** Keep PHP credentials private. When deployed publicly, database configuration and SQL dumps must not be web-accessible; prefer moving private files outside the document root or protecting access with Apache configuration. Do not treat a simple localhost folder layout as a production security design.

## 5. Frontend composition rules

- Full pages (`index.html`, Explore `index.html`, Login/Register `index.html`) contain complete HTML documents.
- Navbar, Footer, and Homepage sections are **HTML fragments**, not nested documents.
- Use the existing `js/include.js` / `pages/home/home.js` loading approach. Check `response.ok`, avoid repeated loading, and initialize interactive JS **after** fragments enter the DOM.
- In fetched fragments, relative image URLs resolve against the **host page URL**, not the fragment file. Verify paths from both Homepage and nested public pages.
- Use unique section IDs (`hero`, `about`, `explore`, `cta`) and distinct mount IDs to avoid duplicates.
- Scope styles by component (`.site-*`, `.hero-*`, `.about-*`, `.explore-*`, `.cta-*`, `.auth-*`). Avoid broad selectors that break unrelated pages.
- Never insert unsanitized user-submitted or database-sourced strings using `innerHTML`; render text using `textContent` or safe DOM creation.

## 6. Homepage & shared components

Homepage order is fixed unless the user changes it:

**Navbar → Hero → About TimeBank → Explore preview → CTA → Footer**.

- **Hero:** left headline/description/buttons, right visual; existing CTAs include `Explore Skills` and `Offer a Skill`.
- **About:** educational explanation plus an ongoing TimeBank cycle: **Teach → Earn → Learn → Repeat**.
- **Explore preview:** six skill categories; distinguish it from the separate Explore Skills page.
- **CTA:** invitation to join, linking to Register when available.
- **Navbar:** floating/persistent on public pages; use the correct TimeBank wordmark, keep spacing below it, and preserve usable mobile navigation.
- **Footer:** reusable, minimal, and linked only to routes/sections that actually exist.
- Do not add new Homepage sections such as FAQ, Gallery, Testimonials, or Featured Tutors without a specific request.

### About-section orbital motion (when implementing/refining it)

- Four cards **orbit clockwise along a circular path** at equal 90-degree spacing; suggested labels: Teach 1 Hour, Earn 1 Credit, Learn 1 Hour, Repeat & Share.
- Every **whole visible card** (background, icon, text) stays **perfectly upright/horizontal** throughout the orbit; use parent rotation plus precisely synchronized counter-rotation or another reliable method.
- Stationary center label, continuous smooth motion (~18 seconds/revolution), no overlap or clipping, pause on hover/focus if present, and a static fallback for `prefers-reduced-motion`.
- Keep the circular layout clean and legible at small widths rather than preserving a broken orbit.

## 7. Authentication UI — current design decision

The approved Login/Register visual direction is **modern minimalist**, calm, and spacious. Do not recreate the earlier overly busy illustrations, patterned backgrounds, dense decorative plants, or large visual clutter.

- **Authentication pages do not need the full public floating Navbar or Footer.** Prefer a **minimal auth header**: TimeBank wordmark on the left and `Back to Home` on the right. Keep public Navbar shared for Homepage/Explore.
- Use a clean two-column desktop layout when space allows: a concise brand/value panel on the left and the form on the right. On mobile, stack or hide nonessential decorative content so the form is prominent.
- Keep background mostly cream/white, with very restrained aqua/coral accents and generous whitespace.
- Follow typography, input/button styles, and responsive tokens in `DESIGN.md`; keep labels visible and focus/error states accessible.
- **Login:** email, password, password visibility toggle, primary Login button, link to Register. `Remember me` and `Forgot password` are optional only if their behavior is honestly handled; do not imply a recovery flow exists.
- **Register:** full name, email, password, confirm password, primary Create Account button, link to Login; short explanation that one account supports learning and teaching.
- Do not add functional Google/Apple/GitHub sign-in buttons unless actual OAuth integration is requested. Omit unsupported actions or clearly label nonfunctional visual mockups.
- Do not claim successful registration, login, or account security without real server validation.

## 8. Minimal PHP–MySQL integration rules

When implementing Register/Login, keep endpoints straightforward. Use the **existing** `timebank_db` schema rather than inventing new authentication tables.

Relevant existing tables:

- `users`: `user_id`, `full_name`, `email`, `password_hash`, `role` (`member`/`admin`), `account_status`, timestamps.
- `user_profiles`: optional profile record linked to `users.user_id`.
- `wallets`: optional initial wallet record, with initial balance **0** unless the user defines a different credit policy.
- `skill_categories`, `skills`: catalogue data for Explore.

Requirements:

1. Use **PDO with prepared statements** and UTF-8 (`utf8mb4`). Do not interpolate untrusted inputs into SQL.
2. **Register:** server-side validate name/email/password, reject duplicate email, call `password_hash()`, and create the new account as `role = 'member'` regardless of client input. Never allow public registration to assign `admin`.
3. If registration also inserts into `user_profiles`/`wallets`, use a database **transaction** so partial registration does not persist.
4. **Login:** look up by email, verify with `password_verify()`, reject suspended accounts, and regenerate the PHP session ID after successful authentication.
5. **Logout:** clear the session and session cookie appropriately. Keep session cookies HttpOnly and use secure cookie settings when HTTPS is available.
6. Return consistent JSON responses and meaningful HTTP status codes. Do not reveal whether a login email exists; avoid returning password hashes or raw errors to the client.
7. Enforce server-side validation; client-side JS validation is only a usability layer. For state-changing requests, include appropriate CSRF protection (e.g., session-based token) rather than relying on client-side checks alone.
8. Do not save raw passwords, authentication tokens, or database secrets in browser storage or Git. Use a local ignored configuration for database credentials.
9. **Never fake authentication** with `localStorage`, a hardcoded user, or automatic success redirects. Redirect only to an existing page after a real successful response, not to an unfinished Dashboard.
10. Test local PHP endpoints through XAMPP/Apache and verify actual MySQL rows for Register/Login.

Keep security essentials even though this is a school mockup; **do not overengineer** with frameworks or enterprise abstractions.

## 9. Explore Skills & future roles

- Explore Skills may consume `php/get-skills.php` to render real `skill_categories` and `skills` records when that integration is requested.
- Keep loading, empty, error, and successful-data states distinct. Never show static sample data as though it came from the database.
- Public members share one learning/teaching interface. Session-specific learner/tutor roles are not separate `users.role` values.
- Admin UI and authorization rules beyond Login/Register are future tasks. Never grant admin access based only on a frontend flag or hidden link.

## 10. Design, accessibility & testing

- `DESIGN.md` governs visual details: brand palette, Plus Jakarta Sans headings, DM Sans body, spacing, typography, responsive breakpoints, component states, and reduced motion.
- **Design decision update:** Earlier `DESIGN.md` wording may describe auth as frontend-only. The user's newer requirement now authorizes **simple PHP–MySQL authentication integration**. Preserve design rules, but do not let outdated scope text prevent explicitly requested backend work. Do not edit `DESIGN.md` automatically.
- Keep one `<h1>` per page, semantic landmarks, meaningful labels/alt text, keyboard focus, sufficient contrast, and no unwanted horizontal scrolling.
- Verify approximate mobile/tablet/desktop widths (390px, 768px, 1440px).
- For auth: test valid input, invalid email, wrong password, duplicate registration, empty fields, session handling, and no broken redirects.
- For HTML loading: check for failed requests, missing images/styles, duplicate IDs, and runtime console errors.

## 11. Coding assistant workflow

**Before editing:** Read `AGENTS.md`, `DESIGN.md`, and the relevant existing files. Identify which features/files exist. Propose minimal scoped changes when planning is requested.

**During implementation:** Work only on the requested feature. Preserve existing working components. Avoid speculative future pages, dependency additions, unrelated refactoring, fake interactions, and unrequested schema changes.

**After implementation:** Test what can actually run, report modified files and verification results, and distinguish finished functionality from planned/mock functionality. Do not claim successful DB integration unless the endpoints and queries were exercised against the local database.

## 12. Academic deliverables

The IS556 assignment includes design artifacts (storyboard, wireframe, site diagram, user flowchart), frontend demonstration, multimedia integration/explanation, and database schema. PHP–MySQL integration is an **additional implementation choice** for TimeBank; do not assume it replaces or changes the assignment requirements.

---

**Guiding principle:** Build the smallest working TimeBank feature with a consistent visual language, honest functionality, and clear separation between HTML/CSS/JS pages and minimal PHP database operations.
