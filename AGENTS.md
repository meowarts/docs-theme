# Repository Guidelines

## Project Structure & Module Organization
- `assets/scss/`: Source styles. Entry `theme-header.scss` compiles to `style.min.css`.
- `assets/js/`: Front-end scripts (`theme.js`, `search.js`, `async-navigation.js`).
- `blocks/table-of-contents/`: Custom block (`block.json`, `render.php`, build assets).
- `inc/features/`: Modular PHP features loaded via `inc/theme-features.php`.
- Theme core: `functions.php`, `style.css`/`style.min.css`, templates (`header.php`, `footer.php`, `page.php`, `single.php`), `template-parts/`.

## Build, Test, and Development Commands
- `npm install`: Install dev dependencies (Sass compiler).
- `npm run build`: Compile SCSS to `style.min.css` and prepend theme header.
- `node deploy.js`: Bump date-based version, build, stage files, and deploy via SFTP using `.env`.
- Example `.env`: copy from `.env.example` and set `SFTP_HOST`, `SFTP_USER`, `SFTP_PASSWORD`, optional `SFTP_REMOTE_PATH`.

## Coding Style & Naming Conventions
- PHP: Follow WordPress standards (tabs, snake_case), namespace `DocsTheme`. Place new features in `inc/features/` (kebab-case filenames) and load from `inc/theme-features.php`.
- JS: Vanilla, IIFE modules, match existing tab indentation and semicolons. Keep DOM selectors specific (e.g., `.docs-toc__item`).
- SCSS/CSS: Edit only files in `assets/scss/`; do not hand-edit generated `style.min.css`. Use existing variables and BEM-ish class names (`docs-toc__link`).

## Testing Guidelines
- No automated tests. Verify manually on a WordPress site:
  - Activate theme, visit pages with H2–H4 headings; confirm ToC renders and anchors scroll smoothly.
  - Check code blocks: copy buttons and syntax highlighting work.
  - Verify page categories appear in REST and sidebar ordering behaves.
- Always run `npm run build` before validating or submitting PRs.

## Commit & Pull Request Guidelines
- Commits: concise, imperative mood. Reflect common history patterns, e.g., `Deploy version 25.8.1 - <summary>` or a focused change description.
- PRs must include: clear description, before/after screenshots for UI changes, test steps, any related issues/links, and notes if deployment/build is impacted.
- Hygiene: exclude secrets and noise (`.env`, `node_modules`, maps). Keep diffs minimal and scoped.

## Security & Configuration Tips
- Keep `.env` out of version control; use `.env.example` as a template.
- Validate SFTP credentials and remote path before `node deploy.js`. Never embed secrets in PHP/JS.

## Navigation & Child Pages Notes (Learnings)
- Sidebar parents must be navigable: render parent items as `<a class="page-link page-parent" href="…">` and use a separate caret button (`.toggle-children`) to expand/collapse. Avoid capturing clicks on the parent title; only the caret should toggle.
- SPA vs non‑SPA consistency: use the same query/filter for child pages in both paths. Apply `apply_filters('docs_theme_child_pages_query', $args, $page_id)` in PHP both in `page.php` and the async REST (`inc/features/async-navigation.php`).
- Child page cards: render as anchors and include `data-page-id` for SPA interception; safe for non‑SPA.
- ToC/sidebar on parent pages: when a page has child cards, do not show the right sidebar ToC; when leaf pages, show ToC only if there are real headings (H2–H4).
- Caching during local testing: the theme strips version query strings; hard refresh or use an incognito window to ensure updated JS/CSS loads.
- Pointer events: `.is-loading` utility sets `pointer-events: none;` on the target; ensure it’s not left on containers that should remain clickable.
