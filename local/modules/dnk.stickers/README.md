# dnk.stickers

Extensible Bitrix module for catalog HIT sticker assignment tracking.

## What it does (v1)

- Tracks when a sticker (by HIT enum `XML_ID`) was assigned to a product in table `b_dnk_stickers_assignment`.
- **Remember**: scan products that already have the sticker and store `ASSIGNED_AT = now` (HIT unchanged; existing rows are not overwritten).
- **Auto on create**: merge-add configured stickers when a catalog element is created.
- **Manual**: detect manager add/remove of tracked stickers on element update.
- **Assign by filter**: admin button runs `CIBlockElement::GetList` with rule `assign_filter` JSON, merge-adds the sticker and tracks assignments (does not remove stickers outside the filter).
- **Expire** (agent / admin button): remove only the expired sticker enum from HIT; other HIT values stay.

v1 ships one rule: `xml_id = NEW` (Новинка). More stickers can be added later via the same `rules` config without new tables.

## Install

1. Copy/ensure module path: `local/modules/dnk.stickers/`.
2. Install in Bitrix admin: Settings → Modules → dnk.stickers.
3. Configure: Settings → Module settings → dnk.stickers.
4. Run **Запомнить текущие** once after install if products already have NEW.
5. **Manually disable** Aspro Premier agents `Aspro\Premier\Agents\Stickers\Novinka::run` / `runOne` if they are still active (this module does not remove them).

## Options

| Option | Default | Notes |
|--------|---------|--------|
| enabled | Y | Global switch |
| iblock_id | 42 | Catalog iblock |
| hit_property_code | HIT | Multiple list property |
| batch_size | 100 | Remember / expire / filter batches |
| agent_interval | 3600 | Seconds |
| rules (JSON) | NEW rule | lifetime_days, auto_on_create, track_manual, assign_filter |

### assign_filter example

```json
{
  "ACTIVE": "Y",
  "SECTION_ID": 691
}
```

`IBLOCK_ID` is always forced from module settings. Empty filter → «Установить по фильтру» does nothing.

## PHP API

- `Dnk\Stickers\StickerService::rememberExisting('NEW')`
- `Dnk\Stickers\StickerService::rememberAllEnabled()`
- `Dnk\Stickers\StickerService::assignByFilter('NEW')` / `assignByFilterAllEnabled()`
- `Dnk\Stickers\StickerService::expire('NEW')` / `expireAll()`
- `Dnk\Stickers\Agent::run()` — Bitrix agent
