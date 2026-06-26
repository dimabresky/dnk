# Модуль Aspro Bonus (`aspro.bonus`)

Документация по устройству бонусов в проекте: баланс, списание, связь с заказом, получение данных по списанию.

Исходный код модуля: `bitrix/modules/aspro.bonus/`.

---

## Где хранится баланс

Баланс пользователя хранится **не** во внутреннем счёте `sale`, а в **пользовательском поле** `UF_ASPRO_BONUS_COUNT`.

Класс: `Aspro\Bonus\History\User` (`lib/history/user.php`).

---

## История операций

Таблица ORM: `Aspro\Bonus\ORM\HistoryOperationsTable`  
Имя таблицы в БД: `b_aspro_bonus_history_opertations`.

Полезные поля: `TYPE`, `ORDER_ID`, `USER_ID`, `SUMM_BONUSES`, `BONUSES_BEFORE`, `BONUSES_AFTER`, `PAYMENT_ID`, `DETAIL_INFO`, `CUSTOM_INFO`, `BALANCE`, `USED_BONUSES` (для цепочки начислений).

Типы операций задаются enum `Aspro\Bonus\Enums\HistoryOperations` (например: `ADD_BY_ORDER`, `MINUS_FROM_ORDER`, `MINUS_BY_ORDER_CANCEL` и др.).

---

## Свойства заказа

Класс: `Aspro\Bonus\History\Order` (`lib/history/order.php`).

| Код свойства          | Константа в коде              | Назначение                          |
|-----------------------|-------------------------------|-------------------------------------|
| `ASPRO_BONUS_PAYED`   | `PROPERTY_BONUS_PAYMENT`      | Сколько бонусов списано по заказу   |
| `ASPRO_BONUS_ADDED`   | `PROPERTY_BONUS_COUNT`        | Начислено бонусов по заказу (после) |

---

## Платёжная система «бонусы»

Код ПС: `ASPRO_BONUS_PAYMENT`.  
Класс: `Aspro\Bonus\Paysystem` (`lib/paysystem.php`).

Используется в режиме **частичной оплаты заказа бонусами** (`PARTIAL_PAYMENT_ORDER`): создаётся отдельный платёж на сумму списания бонусов.

---

## Подключение к событиям Sale

Регистрация в `install/index.php` (`InstallEvents` / `getCompatibleEvents`):

| Событие                      | Обработчик                          |
|-----------------------------|-------------------------------------|
| `OnSaleOrderBeforeSaved`    | `SaleOrder::onSaleOrderBeforeSavedHandler` |
| `OnSaleOrderSaved`          | `SaleOrder::onSaleOrderSavedHandler`       |
| `OnSaleOrderCanceled`       | `SaleOrder::onSaleOrderCanceledHandler`    |
| `OnSaleOrderDeleted`        | `SaleOrder::onSaleOrderDeletedHandler`     |
| `OnSaleOrderPaid`           | `SaleOrder::onSaleOrderPaidHandler`        |
| `OnSaleStatusOrderChange`   | `SaleOrder::onSaleStatusOrderChangeHandler` |

Дополнительно: `OnSaleComponentOrderResultPrepared` → `SaleOrderAjax` (оформление заказа в компоненте).

---

## Поток оформления заказа

### 1. До сохранения заказа (`OnSaleOrderBeforeSaved`)

`Aspro\Bonus\Processing\BeforeOrderSave::handle`

- Вызывается компонент `aspro:bonus.uses` с корзиной/параметрами заказа.
- В зависимости от настройки **`PAY_BONUSES_TYPE`** (`Aspro\Bonus\Config::getPayBonusesType`, опция модуля `PAY_BONUSES_TYPE`):
  - **Скидка** (`DISCOUNT_ORDER_SAVE` / `INSTANT_DISCOUNT`): пересчитываются цены позиций корзины и/или доставки, корректируются суммы платежей.
  - **Частичная оплата** (`PARTIAL_PAYMENT_ORDER`): создаётся платёж с ПС бонусов (`ASPRO_BONUS_PAYMENT`), сумма из расчёта компонента.

Enum: `Aspro\Bonus\Enums\PayBonusesType` (`lib/enums/paybonusestype.php`).

### 2. После сохранения нового заказа (`OnSaleOrderSaved`)

`Aspro\Bonus\Processing\AfterOrderSave::handle`

- Срабатывает только для **нового** заказа (см. `SaleOrder::onSaleOrderSavedHandler`).
- Читает свойство заказа с кодом **`ASPRO_BONUS_PAYED`**. Если значение пустое — списание с баланса не выполняется.
- При ненулевом значении вызывается `Aspro\Bonus\Processing\Api\MinusBonuses::handle` с типом операции **`MINUS_FROM_ORDER`**, суммой и при необходимости `PAYMENT_ID` платежа с ПС бонусов.

---

## Что происходит при списании

`Aspro\Bonus\Processing\Api\MinusBonuses::handle`:

1. `Aspro\Bonus\History\Operations::createMinusByOrder` — запись в истории операций.
2. `Aspro\Bonus\History\User::minusBonuses` — уменьшение `UF_ASPRO_BONUS_COUNT`.

При создании записи списания вызывается логика **`processUsedOperations`** (`History\Operations`): списание распределяется по ранее начисленным операциям (поле `BALANCE` у записей начисления), по сути **FIFO** по активным начислениям.

Отмена заказа и возврат бонусов обрабатываются методами `MinusBonuses::cancelByOrder`, `cancelOrderCancel` и т.д.

---

## Начисление бонусов (кратко)

`Aspro\Bonus\Processing\Api\AddBonuses::handle` — начисление по правилам модуля (компонент `aspro:bonus.calculate`).

Триггеры в `Aspro\Bonus\Events\SaleOrder`:

- `OnSaleOrderPaid` — при полной оплате (если включено `EARN_WHEN_ORDER_FULL_PAYED` в `Config`).
- `OnSaleStatusOrderChange` — при переходе в статус из настройки `EARN_WHEN_ORDER_STATUS` (по умолчанию в конфиге фигурирует `F`).

Настройки: `Aspro\Bonus\Config` (`lib/config.php`).

---

## Только списание, без начисления

Отдельного флага «только расход» в коде нет. Практически начисление отключают:

- настройками и профилями начисления в админке модуля;
- статусом/условиями так, чтобы `AddBonuses` не вызывался;
- при необходимости — обработчиками событий модуля (`beforeCreateAddByOrder` и др.).

---

## Как получить списанные бонусы по заказу

### Вариант 1: готовый метод

```php
\Bitrix\Main\Loader::includeModule('aspro.bonus');

$operation = \Aspro\Bonus\History\Operations::getMinusFromOrder($orderId, $userId);
// Массив записи операции типа MINUS_FROM_ORDER или пустой массив
```

Учтите: выборка в `getByOrder` упорядочена по `ID` DESC и возвращает одну запись по фильтру типов — для типичного сценария «одно списание на заказ» этого достаточно.

### Вариант 2: прямой запрос к ORM

```php
\Bitrix\Main\Loader::includeModule('aspro.bonus');

use Aspro\Bonus\Helper;
use Aspro\Bonus\Enums\HistoryOperations;
use Aspro\Bonus\ORM\HistoryOperationsTable;

$rows = HistoryOperationsTable::getList([
    'filter' => [
        'ORDER_ID' => $orderId,
        'USER_ID' => $userId,
        'TYPE' => Helper::getString(HistoryOperations::MINUS_FROM_ORDER),
        'ACTIVE' => 'Y',
    ],
    'select' => ['*'],
    'order' => ['ID' => 'DESC'],
]);
```

Ключевое поле суммы списания: **`SUMM_BONUSES`**. Детализация по «кускам» начислений — в **`CUSTOM_INFO`** / **`DETAIL_INFO`**.

---

## События модуля для доработок

В `History\Operations` вызываются `Bitrix\Main\Event` модуля `aspro.bonus`, например:

- `beforeCreateMinusByOrder` / `afterCreateMinusByOrder`
- `beforeCreateAddByOrder` / `afterCreateAddByOrder`

Их можно использовать для интеграций без правки ядра модуля.

---

## Импорт остатка из внешней системы (агент DNK)

Агент `\Dnk\PhpInterface\BonusFetchAgent::runBonusAgent()` обрабатывает JSON-файлы из каталога `DNK_BONUS_CLIENT_IMPORT_DIR` (по умолчанию `upload/clientbonus`), сопоставляет пользователей по телефону (`DNK_BONUS_JSON_KEY_PARTNER_PHONE`, поле `ПартнерНомерТелефона`), синхронизирует баланс через `\Dnk\PhpInterface\Utils::replaceDnkImportBonusesForUser()` и уровень клиента через `\Dnk\PhpInterface\Utils::syncDnkBonusImportUserLevelFromFile()`.

### Порядок обработки файлов

1. В каталоге импорта выбираются файлы `*.json`, сортировка по имени (`SORT_STRING`).
2. Каждый файл читается и разбирается как JSON-массив объектов.
3. При **невалидном JSON** или ошибке чтения запись попадает в лог (`DNK_BONUS_CLIENT_IMPORT_LOG_DIR`), файл **не удаляется**.
4. При успешном разборе строки сопоставляются с пользователями; необработанные телефоны и ошибки пишутся в `{имя_файла_без_расширения}.log` в каталоге логов.
5. После успешного прохода по всем строкам файла JSON **удаляется** (в том числе при частичных ошибках по отдельным телефонам).

### Формат строки JSON

```json
{
  "НачисленоОстаток": 0,
  "ПартнерНомерТелефона": "375296609781",
  "УровеньКлиента": 1,
  "СуммаДляПерехода": 300,
  "ДатаСписание": "07.05.2027",
  "БлижайшееСписание": 1.65
}
```

Ключи полей задаются в `.env`:

- `DNK_BONUS_JSON_KEY_BALANCE` — остаток бонусов;
- `DNK_BONUS_JSON_KEY_PARTNER_PHONE` — телефон;
- `DNK_BONUS_JSON_KEY_CLIENT_LEVEL` — уровень клиента (`UF_LEVEL`);
- `DNK_BONUS_JSON_KEY_NEXT_LEVEL_COST` — сумма для перехода (`UF_NEXT_LEVEL_COST`);
- `DNK_BONUS_JSON_KEY_EXPIRE_DATE` — дата ближайшего списания (`UF_BONUS_EXPIRE_DATE`), по умолчанию `ДатаСписание`;
- `DNK_BONUS_JSON_KEY_EXPIRE_AMOUNT` — сумма ближайшего списания (`UF_BONUS_EXPIRE_AMOUNT`), по умолчанию `БлижайшееСписание`.

Допустимые значения `УровеньКлиента`: `1`, `2`, `3`, `5`. Невалидное значение пишется в лог `[invalid_client_level]`, поле `UF_LEVEL` для строки не обновляется.
Дата списания принимается в формате `дд.мм.гггг`; пустое значение очищает `UF_BONUS_EXPIRE_DATE`. Невалидная непустая дата пишется в лог `[invalid_expire_date]`, сохранённые поля даты и суммы ближайшего списания при этом не обновляются.

### Сопоставление пользователя

- Нормализация телефона до цифр (`Utils::normalizeBonusPhoneDigits`).
- Поиск в `b_user_phone_auth` (`UserPhoneAuthTable`) с учётом канонического формата номера.
- Fallback: `PERSONAL_PHONE` / `WORK_PHONE` с точным совпадением нормализованных цифр.
- При нескольких пользователях на один номер — строка в лог `[ambiguous_phone]`, начисление не выполняется.
- При отсутствии пользователя — строка в лог `[not_found]`.
- Если один телефон встречается в файле несколько раз, используется **последняя** подходящая строка (без суммирования).

Логика синхронизации (`Utils::replaceDnkImportBonusesForUser`):

- **Источник правды:** число из выгрузки (поле остатка) — это целевой баланс бонусов на сайте.
- **История:** физически удаляются **все** строки в `b_aspro_bonus_history_opertations` для данного `USER_ID` (любые типы операций, включая уже неактивные).
- **Баланс:** поле `UF_ASPRO_BONUS_COUNT` сбрасывается в `0` тем же способом, что использует `Aspro\Bonus\History\User` (`USER_FIELD_MANAGER` + `PROPERTY_BONUS_COUNT`), без вызова `minusBonuses` (чтобы не создавать новых записей списания).
- Если остаток **больше нуля**, добавляется **одна** операция типа `ADD_BY_ORDER` с маркером `[DNK_BONUS_IMPORT]` в `DETAIL_INFO`, срок `ACTIVE_TO` — через `DNK_BONUS_IMPORT_ACTIVE_YEARS` лет; затем баланс доводится механизмом модуля до переданной суммы.
- Если остаток **ноль**, новая операция не создаётся: пустая история и нулевой баланс.

Строки с `БонуснаяПрограммаЛояльности`, отличной от настроенного кода программы (`DNK_BONUS_IMPORT_PROGRAM_CODE`), пропускаются при разборе JSON (если поле присутствует в строке).

### Уровень клиента и группы

`Utils::syncDnkBonusImportUserLevelFromFile()`:

- записывает `УровеньКлиента` в `UF_LEVEL`, `СуммаДляПерехода` в `UF_NEXT_LEVEL_COST` (если ключи присутствуют в строке);
- при **изменении** `UF_LEVEL` относительно текущего значения:
  - добавляет `USER_ID` в `b_dnk_user_reauthorize_queue` (без дубля; при следующем запросе пользователь переавторизуется через `Utils::processUserReauthorizeIfNeeded()`);
  - снимает прежнюю уровневую группу и назначает новую (прочие группы пользователя сохраняются).

`Utils::syncDnkBonusImportExpirationFromFile()` записывает `ДатаСписание` в `UF_BONUS_EXPIRE_DATE` и `БлижайшееСписание` в `UF_BONUS_EXPIRE_AMOUNT` (если ключи присутствуют в строке). Пользовательские поля создаются одноразовым скриптом:

```bash
php local/tools/install_bonus_user_fields.php
```

В блоке бонусов личного кабинета предупреждение о списании выводится только если сумма ближайшего списания больше `0`, дата валидна и находится в диапазоне от текущей даты до `+3 months` включительно.

| УровеньКлиента | Группа Bitrix | Название |
|----------------|---------------|----------|
| 1 | 9 | Beauty Basic |
| 2 | 10 | Beauty Medium |
| 3 | 11 | Beauty Premium |
| 5 | 12 | Сотрудник |

> **Примечание:** точечная синхронизация по одному пользователю через HTTP (`DNK_BONUS_ENDPOINT`, очередь `BonusBalanceQueueAgent`, `Utils::trySyncDnkImportBonusesForUserByPhone`) обновляет баланс и при наличии в ответе ключей уровня/списания — `UF_LEVEL` / `UF_NEXT_LEVEL_COST` / `UF_BONUS_EXPIRE_DATE` / `UF_BONUS_EXPIRE_AMOUNT` (та же логика, что при файловом импорте). Массовый импорт агентом `BonusFetchAgent` работает только с файлами.

---

## Ссылки на файлы в репозитории

| Назначение              | Путь |
|-------------------------|------|
| События заказа          | `bitrix/modules/aspro.bonus/lib/events/saleorder.php` |
| До/после сохранения     | `bitrix/modules/aspro.bonus/lib/processing/beforeordersave.php`, `afterordersave.php` |
| Списание/начисление API | `bitrix/modules/aspro.bonus/lib/processing/api/minusbonuses.php`, `addbonuses.php` |
| История и FIFO          | `bitrix/modules/aspro.bonus/lib/history/operations.php` |
| Баланс пользователя     | `bitrix/modules/aspro.bonus/lib/history/user.php` |
| Свойства заказа         | `bitrix/modules/aspro.bonus/lib/history/order.php` |
| Конфиг                  | `bitrix/modules/aspro.bonus/lib/config.php` |
| Регистрация обработчиков| `bitrix/modules/aspro.bonus/install/index.php` |
