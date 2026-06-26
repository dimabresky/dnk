# DNK.BY — интернет-магазин косметики

Проект интернет-магазина на базе **1С-Битрикс: Управление сайтом** (редакция «Интернет-магазин», версия ядра 26.150.0 и выше). В качестве готового решения используется шаблон Аспро «Премьер» и его экосистема.

## Назначение

Сайт предназначен для продажи косметики: каталог, корзина, оформление заказов, интеграция с внешними сервисами (в том числе бонусная программа и обмен данными с учётными системами).

## Стек и особенности

- **Bitrix Framework** — стандартные модули, события, хуки и компоненты.
- **Кастомная логика** — в основном в каталоге `local/`: PHP-интерфейс, классы, миграции.
- **Вспомогательные методы** — централизованы в `local/php_interface/include/classes/Utils.php`.
- **Бонусы** — при доработках учитывать модуль `bitrix/modules/aspro.bonus`. Массовый импорт остатков — из JSON в `upload/clientbonus` (агент `BonusFetchAgent`); подробности в [`local/BONUSES.md`](local/BONUSES.md).

## Структура (кратко)

| Путь | Назначение |
|------|------------|
| `local/php_interface/` | Подключение классов, события, константы, миграции |
| `local/php_interface/include/classes/` | Пользовательские классы и сервисы |
| `bitrix/templates/` | Шаблоны сайта (в т.ч. копии Aspro) |

## Развёртывание

1. Установить Битрикс в соответствии с [документацией](https://dev.1c-bitrix.ru/).
2. Восстановить конфигурацию подключения к БД: `bitrix/php_interface/dbconn.php`, `bitrix/.settings.php` (не коммитить секреты в публичный репозиторий).
3. Настроить константы и интеграции в `local/php_interface/include/constants.php` и окружении под целевой сервер.
4. **Сертификаты:** в `.env` указывается `DNK_CERTIFICATE_CATALOG_IBLOCK_ID` — ID инфоблока номинальных сертификатов (`NOMINAL`, `DETAIL_PICTURE`). Инфоблок заявок: из CLI — `php local/tools/install_certificate_requests_iblock.php` (ID из `.env`) или с аргументом `<ID>`; из браузера под администратором — `/local/tools/install_certificate_requests_iblock.php?run=Y[&cert_iblock_id=<ID>]` (`$GLOBALS['USER']->IsAdmin()`). Добавьте `DNK_CERTIFICATE_REQUEST_IBLOCK_ID` по выводу скрипта. Для создания заявок от гостей выдайте нужной группе право добавления элементов в ИБ заявок. Уведомление менеджера по почте: тип события `CUSTOM_MAIL`, в `.env` задайте `DNK_CERTIFICATE_REQUEST_MAIL_TEMPLATE_ID` — ID строки нужного почтового шаблона (параметры письма `#IBLOCK_ID#`, `#ID#`, `#DETAIL_INFO#`).
5. Каталог `upload/` и кэши ядра обычно не хранятся в репозитории — см. `.gitignore`.
6. **Импорт бонусов:** в `.env` задайте `DNK_BONUS_CLIENT_IMPORT_DIR` (по умолчанию `upload/clientbonus`) и `DNK_BONUS_CLIENT_IMPORT_LOG_DIR` (`upload/clientbonus_logs`). Внешняя система кладёт JSON-массив объектов с полями `НачисленоОстаток`, `ПартнерНомерТелефона`, `УровеньКлиента`, `СуммаДляПерехода`, `ДатаСписание`, `БлижайшееСписание` (ключи — `DNK_BONUS_JSON_KEY_BALANCE`, `DNK_BONUS_JSON_KEY_PARTNER_PHONE`, `DNK_BONUS_JSON_KEY_CLIENT_LEVEL`, `DNK_BONUS_JSON_KEY_NEXT_LEVEL_COST`, `DNK_BONUS_JSON_KEY_EXPIRE_DATE`, `DNK_BONUS_JSON_KEY_EXPIRE_AMOUNT`; для двух последних есть дефолты по названию поля). Агент `\Dnk\PhpInterface\BonusFetchAgent::runBonusAgent()` сортирует файлы по имени, сопоставляет пользователей по телефону, синхронизирует баланс Aspro Bonus, профиль уровня (`UF_LEVEL`, `UF_NEXT_LEVEL_COST`) и ближайшее списание (`UF_BONUS_EXPIRE_DATE`, `UF_BONUS_EXPIRE_AMOUNT`); при смене уровня — очередь переавторизации и смена группы. User fields для списания создаются через `php local/tools/install_bonus_user_fields.php`. После успешного разбора файл удаляется, ошибки — в логах каталога `clientbonus_logs`. Подробнее: [`local/BONUSES.md`](local/BONUSES.md).

## Компонент покупки сертификатов

- Компонент: `dnk:certificate.buy` (`local/components/dnk/certificate.buy/`).
- Инфоблок каталога и инфоблок заявок задаются только в `.env` / `constants.php`: `DNK_CERTIFICATE_CATALOG_IBLOCK_ID`, `DNK_CERTIFICATE_REQUEST_IBLOCK_ID` — через параметры подключения не передаются.
- Точки самовывоза: `DNK_PICKUP_STORES_IBLOCK_ID` (обычно `11`, активные элементы с `ADDRESS`, `PHONE`, `SCHEDULE`, `MAP`). В свойстве `DELIVERY` инфоблока заявок должно быть значение списка с XML_ID `pickup` («Самовывоз»); для новых установок — через `install_certificate_requests_iblock.php`, на существующем стенде — вручную в админке.
- На странице вставьте:

```php
$APPLICATION->IncludeComponent('dnk:certificate.buy', '', [], false);
```

- Дополнительно доступен параметр только **время кеширования списка** (`CACHE_TIME` через настройку компонента или массив `IncludeComponent`; по умолчанию 3600).
- Почтовое событие `CUSTOM_MAIL`: после успешной заявки в очередь ставится отправка через `Bitrix\Main\Mail\Event::send` по шаблону из `DNK_CERTIFICATE_REQUEST_MAIL_TEMPLATE_ID`; у шаблона должна быть привязка к сайту.

### Статусы заявок

- Свойство списка `STATUS` в инфоблоке заявок: `accepted` (Принят, по умолчанию), `in_progress` (В обработке), `ready` (Готов).
- На **новой** установке свойство создаётся скриптом `install_certificate_requests_iblock.php`. На **существующем** стенде повторно запустите тот же скрипт (CLI или `?run=Y` под админом) — добавится `STATUS` и для старых элементов проставится «Принят».
- При оформлении заявки компонент `dnk:certificate.buy` записывает статус «Принят». Смена статуса — в админке Bitrix (редактирование элемента ИБ).
- Личный кабинет: страница `{SEF_FOLDER личного кабинета}/certificate_requests/` (шаблон `certificate_requests.php`, компонент `dnk:certificate.request.list`) — список заявок пользователя только для просмотра. Ссылка также на странице «Персональные данные».

## Документация для разработки

- [API Битрикс](https://dev.1c-bitrix.ru/api_help/)
- Курсы разработчика и Vue в экосистеме 1С-Битрикс — по ссылкам из внутренней документации проекта.

---

*Внутренний проект; актуальные контакты и окружения — у команды разработки.*
