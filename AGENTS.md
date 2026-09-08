# Agent instructions — DNK.BY

This repository powers **DNK.BY**, a cosmetics e-commerce site on **1C-Bitrix: Site Management** (Online Store edition, core **≥ 26.150.0**), using the **Aspro Premier** template ecosystem. Automated/assisted coding agents should follow this document together with [`README.md`](README.md) and workspace Cursor rules (`.cursor/rules/`). When instructions conflict, follow the order in [If rules conflict](#if-rules-conflict).

## Role and priorities

- Prefer **Bitrix-native APIs**: standard modules, classes, events, and component APIs.
- Keep changes **scoped** to the task; match existing patterns (naming, namespaces, PHP style, how components are structured).
- **Include PHP classes with `use`** where applicable; do not invent parallel frameworks inside the project.
- Follow the [development principles](#general-principles) below (SOLID, DRY, PSR). Do not replace the established `local/php_interface` layout with a generic “everything in modules + DI” architecture.

## Where to put code

| Concern | Location |
|--------|----------|
| Custom PHP logic, services, events | `local/php_interface/` and related paths under `local/` |
| **Shared helpers** | `local/php_interface/include/classes/Utils.php` — centralize reusable helpers here instead of scattering one-off utilities |
| Install / migrate / agent runners | `local/tools/` — one-off or CLI helpers, not production request path |
| Point AJAX endpoints | `local/ajax/` |
| Custom components (`dnk:*`) | `local/components/dnk/` |
| Site templates (including Aspro copies) | `bitrix/templates/` (e.g. `aspro-premier_copy`, `aspro-premier-mobile_copy`) |
| **Shared component templates (desktop + mobile)** | `bitrix/templates/.default/components/<namespace>/<component>/<template>/` — when markup and logic are the same for desktop and mobile site templates |
| **Custom Bitrix modules** | `local/modules/<vendor>.<name>/` — структура `install/`, `lib/`, `include.php`, см. раздел ниже |

Project-specific layout details are summarized in [`README.md`](README.md).

## Custom Bitrix modules (`local/modules/`)

Проект на **1C-Битрикс: Управление сайтом** (редакция «Интернет-магазин», ядро **≥ 26.150.0**). Локальные модули оформляйте как полноценные решения Bitrix Framework:

- Класс модуля наследуйте от `CModule`; **`installDB` / `unInstallDB` / `installFiles` / `unInstallFiles`** объявляйте с той же видимостью, что и в `CModule` (как правило **`public`**), иначе возможны фаталы совместимости при установке.
- Подключение классов: **`use`** для своих namespace, `Loader::registerAutoLoadClasses` или принятый в модуле автозагрузчик; не смешивайте устаревшие глобальные классы без необходимости.
- БД: предпочтительно **D7 ORM** (`DataManager`, таблицы через `install/db/mysql/*.sql` или миграции), явный **`DROP`/удаление опций** в `DoUninstall`; не оставляйте «висячие» HL/таблицы без документации.
- Точки расширения: **`RegisterModuleDependences`**, `\Bitrix\Main\EventManager`, штатные события ядра и модулей — не подключайте произвольные `require` из чужих мест вместо официальных extension points.
- Секреты (ключи API, пароли) не храните в репозитории: только опции модуля на стенде, `.settings.php` / окружение по практике команды.

**Modules in this repo:**

| Module | Notes |
|--------|--------|
| `dnk.stickers` | HIT sticker assignment tracking (NEW / «Новинка»), remember/expire agents — see [`local/modules/dnk.stickers/README.md`](local/modules/dnk.stickers/README.md) |
| `sms.traffic` | SMS via SmartDelivery as Bitrix `messageservice` sender — **git submodule** |
| `bx.imagewebp` | Async iblock image → WebP — **git submodule** |

After clone: `git submodule update --init --recursive`.

**Git** for changes under `local/modules/` — same rules as [Git and delivery](#git-and-delivery): feature branch, Conventional Commits, PR into `dev`, no direct commits to `dev`.

## General principles

- Write code that fits Bitrix Framework and the patterns already used in this repo.
- Do not hardcode business logic in component templates or `result_modifier.php`.
- Keep layers separate:
  - infrastructure (modules, autoload, install, queues, ORM tables),
  - domain logic (services, event handlers, agents),
  - presentation (component templates, include areas, pages).
- Preserve backward compatibility where it is reasonable — especially for public module APIs and component contracts.

Site-specific custom code belongs in `local/php_interface/` (`*Events`, `*Service`, `*Agent`, `*Table` under `Dnk\PhpInterface`). Put a **new module** under `local/modules/` only when the feature needs install/uninstall, its own schema, or independent versioning. Do not migrate existing php_interface code into modules unless the task explicitly asks for that.

## SOLID in this project

### S (Single Responsibility)

- One class / service — one area of responsibility.
- Established split in php_interface:
  - `*Events` — Bitrix event handlers (thin: read event data, call a service, return).
  - `*Service` — domain operations (e.g. `BasketBonusService`, `UserConsentService`, `StickerService` in `dnk.stickers`).
  - `*Agent` — agent entry points and batch work.
  - `*Table` — D7 ORM (`DataManager`) for custom tables.
- Examples: `BasketBonusEvents` + `BasketBonusService`; `OrderExportEvents` + `OrderExportQueueAgent` + `OrderExportQueueTable`.
- Do not put large business logic in module `include.php`, `init.php`, or `component.php`.
- In components, `component.php` prepares data for the template; calculations, integrations, and queues belong in php_interface or module services.
- Controllers (if used) stay thin and delegate to services.

### O (Open/Closed)

- Extend behaviour through:
  - php_interface event classes and `include/events.php`,
  - custom modules and their public APIs,
  - Bitrix events (`EventManager`, `RegisterModuleDependences`),
  - inheritance or decoration only where it fits neighbouring code.
- Do not patch Bitrix core or stock / Aspro modules. Use events, configuration, or a local module instead.

### L (Liskov Substitution)

- When extending Bitrix or project base classes, do not change method contracts (do not strengthen preconditions or weaken postconditions).
- Do not add surprises (unexpected exceptions, hidden side effects).
- If you introduce abstractions (repositories, services), their implementations must be interchangeable.

### I (Interface Segregation)

- Keep interfaces in custom modules small and focused (e.g. a reader vs a writer).
- Do not create “god” interfaces that force clients to implement unused methods.
- If an interface grows, split it. Prefer existing concrete `*Service` classes over new interfaces unless multiple implementations are real.

### D (Dependency Inversion)

- Depend on abstractions where it helps, not on concrete Bitrix classes, when that is practical.
- This repo does **not** use a DI container / `ServiceLocator`. The established pattern is static methods on `Utils`, `*Events`, and `*Service` classes.
- Prefer constructor injection only for **new** non-static services where it fits; do not retrofit static `Utils` / `*Events` into a container.
- Avoid `new` of complex services inside domain classes when a shared static API or a factory already exists.

## DRY and reuse

- Avoid duplicating:
  - domain logic across components and php_interface classes,
  - the same `CIBlockElement` / `Catalog\ProductTable` (and similar) queries,
  - the same event-handler bodies.
- Extract repeats into:
  - `local/php_interface/include/classes/Utils.php` for small shared helpers,
  - a dedicated `*Service` when `Utils` would become a god class,
  - a base component class only if that hierarchy already exists nearby.
- Do not invent parallel helper libraries (`BitrixHelpers`, `CatalogTools`, etc.).
- Do not abstract too early: if logic repeats 2–3 times and may diverge, temporary duplication is acceptable with a comment and a refactor note.

## PSR standards and code style

Follow current PSR standards unless a nearby file has a documented exception:

- **PSR-12** — formatting (indentation, braces, spacing, file structure).
- **PSR-1** — one class / interface / trait per file; PascalCase classes; camelCase methods; `UPPER_CASE` constants.
- **PSR-4** — for custom **modules** (`local/modules/<vendor>.<name>/lib/` → namespaces like `Dnk\Stickers\...`).

php_interface is **not** PSR-4 directory autoload: classes live in `Dnk\PhpInterface` and must be registered in `Loader::registerAutoLoadClasses` in [`local/php_interface/include/include.php`](local/php_interface/include/include.php). Register every new class there.

Bitrix specifics:

- Existing code still uses `C*` classes and globals (`$APPLICATION`, `$USER`, `$DB`). New code should prefer namespaced D7 APIs (`Bitrix\Main`, `Bitrix\Catalog`, `Bitrix\Iblock`) where the framework allows it.
- Do not add large scripts in the site root; use php_interface, modules, components, or `local/tools/` for one-off CLI.

Style requirements:

- Do not mix an ad-hoc style with PSR in the same file.
- Component templates (`template.php`) may be more “template-like”, but keep naming/formatting sane and keep heavy logic out of them.

## Architecture notes

- **php_interface vs modules:** site-specific events, services, agents, and custom tables stay in `local/php_interface/`. Modules are for installable, self-contained features (see [Custom Bitrix modules](#custom-bitrix-modules-localmodules)).
- **Bootstrapping:** `init.php` only includes `include/include.php`. Autoload and constants live there; event **registration** belongs in `include/events.php`; handler **implementations** belong in `*Events` classes. Module handlers stay inside the module (`RegisterModuleDependences` / `EventManager`).
- **Components:** presentation and light orchestration. Heavy work goes to php_interface or module services. Do not duplicate the same component in two site templates when a shared `.default` template applies.
- **Events:** extend stock behaviour with Bitrix events, not core patches. Do not inline handler logic in `init.php`.
- **Data access:** prefer D7 ORM (`Bitrix\Main\ORM`, Catalog / Iblock Data classes) over ad-hoc `CIBlockElement` queries where practical. Put repeated queries and filters into `Utils` or a `*Service`.

## Refactoring and changes

- For non-trivial work (catalog, orders, integrations), outline a short plan before large edits.
- When changing existing code: keep or improve SOLID / DRY / PSR alignment; do not add new violations for a “quick fix”. If a temporary violation is necessary, mark it with a comment and how to remove it.
- Prefer small, incremental diffs that are easy to review in Bitrix (components, events, admin).

## Tests and quality

- This repository has **no PHPUnit harness** today. For behaviour changes, describe a manual check list: which pages, `dnk:*` components, events, agents, or admin screens to verify.
- If a test environment is added later, propose readable PHPUnit tests for new module / php_interface domain logic and keep them in sync with behaviour changes — do not delete tests to make a change pass.
- Manual scenarios should name concrete paths (catalog, checkout, bonus, stickers, feeds) rather than “smoke the site”.

## If rules conflict

1. Explicit user instructions in the current chat.
2. This `AGENTS.md` (narrowest section for the files you are changing).
3. Workspace Cursor rules (`.cursor/rules/`) and [`README.md`](README.md).

If a user request conflicts with these principles, follow the user, but note risks (SOLID/DRY/PSR drift, Bitrix upgradeability, maintenance). Project-specific layout in [Where to put code](#where-to-put-code) wins over generic Bitrix “put everything in a module” advice.

## Bitrix and Aspro conventions

- Use **event handlers** and **standard Bitrix hooks** instead of ad-hoc hooks when an official extension point exists; do not duplicate core behaviour when a supported API exists.
- **Bonuses / loyalty**: before changing related behaviour, review `bitrix/modules/aspro.bonus` and [`local/BONUSES.md`](local/BONUSES.md).
- **Catalog HIT stickers (NEW)**: use `local/modules/dnk.stickers`. After install, manually disable Aspro Premier agents `Aspro\Premier\Agents\Stickers\Novinka::run` / `runOne` if they are still active (the module does not remove them).
- **Component templates**:
  - Do **not** manually include `./script.js` or `./style.css` — they are loaded automatically.
  - Do **not** manually include lang files — they are loaded automatically.
  - If desktop (`aspro-premier_copy`) and mobile (`aspro-premier-mobile_copy`) need the **same** component template (same markup and logic), place it once under `bitrix/templates/.default/components/<namespace>/<component>/<template>/` instead of duplicating into both site templates. Bitrix resolves `.default` for any site template when a site-specific override is absent. Use site-specific template dirs only when desktop and mobile must diverge.
- **CSS**: add rules in the component’s `styles.css`, or in  
  `bitrix/templates/aspro-premier_copy/css/custom.css` or  
  `bitrix/templates/aspro-premier-mobile_copy/css/custom.css`, depending on context (desktop vs mobile template).

## Documentation and references

| Resource | URL |
|----------|-----|
| Курс разработчика 1C-Битрикс | https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43 |
| Курс Vue в 1C-Битрикс | https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=176&INDEX=Y |
| Документация D7 ORM | https://dev.1c-bitrix.ru/api_d7/ |
| API справка (старое ядро) | https://dev.1c-bitrix.ru/api_help/ |
| Документация Аспро Премьер | https://aspro.ru/docs/course/?COURSE_ID=69 |

## Git and delivery

Follow **feature-pr-flow**:

1. Branch from the latest **`dev`** (`feat/…` or `fix/…`).
2. Implement only the scoped task.
3. Commit with English [**Conventional Commits**](https://www.conventionalcommits.org/en/v1.0.0/) (`feat:`, `fix:`, `chore:`, `docs:`, `refactor:`, etc.).
4. Open a pull request into **`dev`** — do **not** commit directly to `dev` or `master`.
5. Resolve CI / review feedback on the PR.
6. Merge the PR (and delete the remote feature branch) only when the PR is green/mergeable and after explicit confirmation when working with an agent.

**Production deploy:** merged pull request into **`master`** triggers [`.github/workflows/deploy-production.yml`](.github/workflows/deploy-production.yml) on a self-hosted runner: update `/home/bitrix/dnk` (`git pull` + submodule sync/update), then `rsync` into web root `/home/bitrix/www` (excludes `.git/`, `.github/`).

Do not commit secrets (e.g. `bitrix/php_interface/dbconn.php`, `bitrix/.settings.php`); follow `.gitignore` and team practice from `README.md`.

## Out of scope for agents unless explicitly requested

- Broad refactors unrelated to the task.
- New top-level documentation files beyond what maintainers ask for.
- Changes that break Bitrix upgrade paths or bypass standard extension points without clear justification.

Prefer small, reviewable diffs; every line should serve the task. When in doubt, stay consistent with neighbouring code and Bitrix documentation for the edition and version in use.
