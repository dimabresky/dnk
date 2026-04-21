# Agent instructions — DNK.BY

This repository powers **DNK.BY**, a cosmetics e-commerce site on **1C-Bitrix: Site Management** (Online Store edition, core **≥ 26.150.0**), using the **Aspro Premier** template ecosystem. Automated/assisted coding agents should follow this document together with [`README.md`](README.md) and workspace Cursor rules.

## Role and priorities

- Prefer **Bitrix-native APIs**: standard modules, classes, events, and component APIs.
- Keep changes **scoped** to the task; match existing patterns (naming, namespaces, PHP style, how components are structured).
- **Include PHP classes with `use`** where applicable; do not invent parallel frameworks inside the project.

## Where to put code

| Concern | Location |
|--------|----------|
| Custom PHP logic, services, events | `local/php_interface/` (see below) |
| **Shared helpers** | `local/php_interface/include/classes/Utils.php` |
| Site templates (including Aspro copies) | `bitrix/templates/` |
| Custom components | `local/components/` |

Project-specific layout details are summarized in [`README.md`](README.md).

## Bitrix and Aspro conventions

- Use **event handlers** and **standard Bitrix hooks** instead of ad-hoc hooks when an official extension point exists.
- **Bonuses / loyalty**: before changing related behaviour, review `bitrix/modules/aspro.bonus`.
- **Component templates**:
  - Do **not** manually include `./script.js` or `./style.css` — they are loaded automatically.
  - Do **not** manually include lang files — they are loaded automatically.
- **CSS**: add rules in the component’s `styles.css`, or in  
  `bitrix/templates/aspro-premier_copy/css/custom.css` or  
  `bitrix/templates/aspro-premier-mobile_copy/css/custom.css`, depending on context (desktop vs mobile template).

## Documentation and references

When implementing or debugging, align with:

- Bitrix API and developer materials (see internal `@docs` references: 1C-Bitrix API, developer course, Vue course where relevant).
- **IMask** and **Swiper** only as documented for this project (`@docs/IMask`, `@docs/Swiperjs`).

## Git and commits

- Commit messages: **English**, [**Conventional Commits**](https://www.conventionalcommits.org/en/v1.0.0/) (`feat:`, `fix:`, `chore:`, etc.).
- Do not commit secrets (e.g. `bitrix/php_interface/dbconn.php`, `bitrix/.settings.php`); follow `.gitignore` and team practice from `README.md`.

## Out of scope for agents unless explicitly requested

- Broad refactors unrelated to the task.
- New top-level documentation files beyond what maintainers ask for.
- Changes that break Bitrix upgrade paths or bypass standard extension points without clear justification.

When in doubt, stay consistent with neighbouring code and Bitrix documentation for the edition and version in use.
