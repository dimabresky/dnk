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

Агент `\Dnk\PhpInterface\BonusFetchAgent::runBonusAgent()` загружает JSON с `DNK_BONUS_ENDPOINT`, сопоставляет пользователей по UUID в поле `XML_ID` пользователя и вызывает `\Dnk\PhpInterface\Utils::replaceDnkImportBonusesForUser()`.

Логика синхронизации:

- Находятся активные операции начисления (`ADD_BY_ORDER`) с маркером `[DNK_BONUS_IMPORT]` в `DETAIL_INFO`; по остатку `BALANCE` уменьшается `UF_ASPRO_BONUS_COUNT`, записи деактивируются (`ACTIVE = N`).
- Затем создаётся новое начисление с тем же типом, срок действия `ACTIVE_TO` — через `DNK_BONUS_IMPORT_ACTIVE_YEARS` лет (по умолчанию 2).
- Начисления по заказам (без маркера импорта) не затрагиваются.

Строки с `БонуснаяПрограммаЛояльности`, отличной от `dnk`, пропускаются. UUID только из `КонтрагентыUUID` (внутри строки дубликаты убираются). Для каждого UUID берётся значение `НачисленоОстаток` как готовый остаток: суммирования нет; если один UUID встречается в нескольких строках ответа, используется строка, идущая позже в массиве. Нулевой остаток синхронизирует баланс импорта в ноль.

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
