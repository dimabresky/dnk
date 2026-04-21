# Agent instructions — DNK.BY

This repository powers **DNK.BY**, a cosmetics e-commerce site on **1C-Bitrix: Site Management** (Online Store edition, core **≥ 26.150.0**), using the **Aspro Premier** template ecosystem. Automated/assisted coding agents should follow this document together with [`README.md`](README.md) and workspace Cursor rules (`.cursor/rules/`). When instructions conflict, follow the **narrowest** rule for the task at hand, then **project-specific** rules over generic advice.

## Role and priorities

- Prefer **Bitrix-native APIs**: standard modules, classes, events, and component APIs.
- Keep changes **scoped** to the task; match existing patterns (naming, namespaces, PHP style, how components are structured).
- **Include PHP classes with `use`** where applicable; do not invent parallel frameworks inside the project.

## Where to put code

| Concern | Location |
|--------|----------|
| Custom PHP logic, services, events | `local/php_interface/` and related paths under `local/` |
| **Shared helpers** | `local/php_interface/include/classes/Utils.php` — centralize reusable helpers here instead of scattering one-off utilities |
| Site templates (including Aspro copies) | `bitrix/templates/` (e.g. `aspro-premier_copy`, `aspro-premier-mobile_copy`) |
| Custom components | `local/components/` |

Project-specific layout details are summarized in [`README.md`](README.md).

## Bitrix and Aspro conventions

- Use **event handlers** and **standard Bitrix hooks** instead of ad-hoc hooks when an official extension point exists; do not duplicate core behaviour when a supported API exists.
- **Bonuses / loyalty**: before changing related behaviour, review `bitrix/modules/aspro.bonus`.
- **Component templates**:
  - Do **not** manually include `./script.js` or `./style.css` — they are loaded automatically.
  - Do **not** manually include lang files — they are loaded automatically.
- **CSS**: add rules in the component’s `styles.css`, or in  
  `bitrix/templates/aspro-premier_copy/css/custom.css` or  
  `bitrix/templates/aspro-premier-mobile_copy/css/custom.css`, depending on context (desktop vs mobile template).

## Documentation and references

When implementing or debugging, align with internal documentation (paths may be exposed as `@docs` in the workspace):

- `@docs/1C-Bitrix api`
- `@docs/1C-Bitrix Разработчик курс`
- `@docs/1C-Bitrix Vue курс`
- `@docs/IMask`
- `@docs/Swiperjs`

Public API reference: [Bitrix dev docs](https://dev.1c-bitrix.ru/api_help/).

## Git and delivery

- **Commits:** English messages; [**Conventional Commits**](https://www.conventionalcommits.org/en/v1.0.0/) (`feat:`, `fix:`, `chore:`, `refactor:`, etc.).
- **Workflow:** use feature branches; keep changes **focused** on the requested task — avoid unrelated refactors or broad formatting-only edits unless the task requires them.
- Do not commit secrets (e.g. `bitrix/php_interface/dbconn.php`, `bitrix/.settings.php`); follow `.gitignore` and team practice from `README.md`.

## Out of scope for agents unless explicitly requested

- Broad refactors unrelated to the task.
- New top-level documentation files beyond what maintainers ask for.
- Changes that break Bitrix upgrade paths or bypass standard extension points without clear justification.

Prefer small, reviewable diffs; every line should serve the task. When in doubt, stay consistent with neighbouring code and Bitrix documentation for the edition and version in use.
