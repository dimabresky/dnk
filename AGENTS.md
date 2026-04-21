# Agent instructions — DNK.BY

This file guides AI coding agents and human contributors working on this repository. It complements `README.md` and Cursor workspace rules (`.cursor/rules/`). When instructions conflict, follow the **narrowest** rule for the task at hand, then **project-specific** rules over generic advice.

## Project

- **Domain:** E-commerce for cosmetics (catalog, cart, checkout, integrations).
- **Platform:** **1C-Bitrix: Site Management**, Internet Store edition, **core ≥ 26.150.0**.
- **Theme:** Aspro «Premier»; site templates live under `bitrix/templates/` (including copies such as `aspro-premier_copy` / `aspro-premier-mobile_copy`).

## Bitrix conventions

- Prefer **standard Bitrix APIs**: modules, classes, events, and component lifecycle. Do not bypass the framework when a supported API exists.
- Use **`use` statements** for class includes.
- Register logic via **events and hooks** where appropriate; do not duplicate core behavior.
- **Bonus system:** For anything related to bonuses, review the module at `bitrix/modules/aspro.bonus`.

## Where to put code

- **Custom PHP** generally belongs under `local/` (e.g. `local/php_interface/`, components).
- **Shared helpers:** Centralize reusable helpers in `local/php_interface/include/classes/Utils.php` (do not scatter one-off utilities across the tree if they belong there).

## Front-end and templates

- **Component templates:** Do **not** manually include `./script.js` or `./style.css` — they are loaded automatically.
- **Component templates:** Do **not** manually include language files — they are loaded automatically.
- **CSS:** Add styles in the component template’s `styles.css`, or in:
  - `bitrix/templates/aspro-premier_copy/css/custom.css`, or
  - `bitrix/templates/aspro-premier-mobile_copy/css/custom.css`  
  (choose based on desktop vs mobile template in use).

## Documentation and references

Use the project’s internal docs where available:

- `@docs/1C-Bitrix api`
- `@docs/1C-Bitrix Разработчик курс`
- `@docs/1C-Bitrix Vue курс`
- `@docs/IMask`
- `@docs/Swiperjs`

Public API reference: [Bitrix dev docs](https://dev.1c-bitrix.ru/api_help/).

## Git and delivery

- **Commits:** English messages; **Conventional Commits** (e.g. `feat:`, `fix:`, `refactor:`).
- **Workflow:** Use feature branches; keep changes **focused** on the requested task — avoid unrelated refactors or broad formatting-only edits unless the task requires them.

## Quality bar

- Match existing naming, structure, and patterns in touched files.
- Prefer small, reviewable diffs; every line should serve the task.
- Do not add unsolicited documentation files unless the task asks for them.
