# dnk:user.bonus.background.sync

Фоновая синхронизация бонусного баланса авторизованного пользователя по телефону через `DNK_BONUS_ENDPOINT` (логика в `Dnk\PhpInterface\Utils::trySyncDnkImportBonusesForUserByPhone`).

## Условия работы

- Пользователь **авторизован** (иначе `executeComponent()` завершается без вывода).
- Текущая страница в одной из зон:
  - главная (`/`, `/index.php`, `CSite::InDir('/index.php')`);
  - `/personal/`;
  - `/basket/`.

## Пример подключения (не подключать автоматически — только вручную на нужных шаблонах)

```php
<?php
$APPLICATION->IncludeComponent(
    'dnk:user.bonus.background.sync',
    '',
    [
        'BALANCE_SELECTOR' => '.js-dnk-bonus-balance',
        'AUTO_REFRESH' => 'Y',
    ],
    false,
    ['HIDE_ICONS' => 'Y']
);
?>
```

### Селектор для блока бонусов в ЛК (Aspro personal)

```php
'BALANCE_SELECTOR' => '.personal__main-private__wrapper--bonuses .personal__main-private__value',
```

На страницах, где выводится баланс, добавьте класс `js-dnk-bonus-balance` к элементу с суммой или передайте свой селектор в параметре `BALANCE_SELECTOR`.

## AJAX

При загрузке страницы (если `AUTO_REFRESH=Y`) вызывается action `refresh` через `BX.ajax.runComponentAction` (POST + CSRF + авторизация).

Ответ:

```json
{
  "success": true,
  "balance": 123.45,
  "balanceFormatted": "123,45",
  "balanceUnit": "б.",
  "errorCode": "optional"
}
```
