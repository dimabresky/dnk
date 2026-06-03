# dnk:user.bonus.background.sync

Фоновая синхронизация бонусного баланса авторизованного пользователя по телефону через `DNK_BONUS_ENDPOINT` (логика в `Dnk\PhpInterface\Utils::trySyncDnkImportBonusesForUserByPhone`). Баланс обновляется **на сервере** (Aspro Bonus); DOM на странице не меняется.

## Условия работы

- Пользователь **авторизован** (иначе `executeComponent()` завершается без вывода).
- Текущая страница в одной из зон: главная (`CSite::InDir('/index.php')` и др.), `/personal/`, `/basket/`.

## Пример подключения (не подключать автоматически — только вручную на нужных шаблонах)

```php
<?php
$APPLICATION->IncludeComponent(
    'dnk:user.bonus.background.sync',
    '',
    [
        'AUTO_REFRESH' => 'Y',
    ],
    false,
    ['HIDE_ICONS' => 'Y']
);
?>
```

`script.js` в шаблоне компонента **не подключать вручную** — Bitrix загружает его автоматически (см. `AGENTS.md`).

## AJAX

При загрузке страницы (если `AUTO_REFRESH=Y`) вызывается action `refresh` через `BX.ajax.runComponentAction` (POST + CSRF + авторизация).

Ответ:

```json
{
  "success": true,
  "errorCode": "optional"
}
```
