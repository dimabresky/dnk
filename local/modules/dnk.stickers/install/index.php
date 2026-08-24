<?php

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

/**
 * Installer for local module dnk.stickers.
 */
class dnk_stickers extends CModule
{
    /** @var string */
    public $MODULE_ID = 'dnk.stickers';

    /** @var string */
    public $MODULE_VERSION;

    /** @var string */
    public $MODULE_VERSION_DATE;

    /** @var string */
    public $MODULE_NAME;

    /** @var string */
    public $MODULE_DESCRIPTION;

    /** @var string */
    public $PARTNER_NAME;

    /** @var string */
    public $PARTNER_URI;

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];

        $this->MODULE_NAME = Loc::getMessage('DNK_STICKERS_MODULE_NAME') ?: 'DNK stickers';
        $this->MODULE_DESCRIPTION = Loc::getMessage('DNK_STICKERS_MODULE_DESC')
            ?: 'Extensible HIT sticker assignment tracking for catalog products.';
        $this->PARTNER_NAME = Loc::getMessage('DNK_STICKERS_PARTNER_NAME') ?: 'DNK';
        $this->PARTNER_URI = Loc::getMessage('DNK_STICKERS_PARTNER_URI') ?: 'https://dnk.by';
    }

    public function DoInstall(): bool
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->installDB();
        $this->installEvents();
        $this->installAgent();

        return true;
    }

    public function DoUninstall(): bool
    {
        $this->unInstallAgent();
        $this->unInstallEvents();
        $this->unInstallDB();
        Option::delete($this->MODULE_ID);
        ModuleManager::unRegisterModule($this->MODULE_ID);

        return true;
    }

    public function installDB(): bool
    {
        $connection = Application::getConnection();
        $sqlFile = __DIR__ . '/db/mysql/install.sql';
        if (is_readable($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            if (is_string($sql) && $sql !== '') {
                foreach ($this->splitSql($sql) as $query) {
                    $connection->queryExecute($query);
                }
            }
        }

        return true;
    }

    public function unInstallDB(): bool
    {
        $connection = Application::getConnection();
        $sqlFile = __DIR__ . '/db/mysql/uninstall.sql';
        if (is_readable($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            if (is_string($sql) && $sql !== '') {
                foreach ($this->splitSql($sql) as $query) {
                    $connection->queryExecute($query);
                }
            }
        }

        return true;
    }

    public function installFiles(): bool
    {
        return true;
    }

    public function unInstallFiles(): bool
    {
        return true;
    }

    public function installEvents(): void
    {
        $em = EventManager::getInstance();
        $em->registerEventHandler(
            'iblock',
            'OnAfterIBlockElementAdd',
            $this->MODULE_ID,
            '\\Dnk\\Stickers\\Handlers',
            'onAfterIBlockElementAdd'
        );
        $em->registerEventHandler(
            'iblock',
            'OnAfterIBlockElementUpdate',
            $this->MODULE_ID,
            '\\Dnk\\Stickers\\Handlers',
            'onAfterIBlockElementUpdate'
        );
    }

    public function unInstallEvents(): void
    {
        $em = EventManager::getInstance();
        $em->unRegisterEventHandler(
            'iblock',
            'OnAfterIBlockElementAdd',
            $this->MODULE_ID,
            '\\Dnk\\Stickers\\Handlers',
            'onAfterIBlockElementAdd'
        );
        $em->unRegisterEventHandler(
            'iblock',
            'OnAfterIBlockElementUpdate',
            $this->MODULE_ID,
            '\\Dnk\\Stickers\\Handlers',
            'onAfterIBlockElementUpdate'
        );
    }

    public function installAgent(): void
    {
        $interval = (int) Option::get($this->MODULE_ID, 'agent_interval', '3600');
        if ($interval < 60) {
            $interval = 3600;
        }

        \CAgent::AddAgent(
            '\\Dnk\\Stickers\\Agent::run();',
            $this->MODULE_ID,
            'N',
            $interval,
            '',
            'Y',
            '',
            100
        );
    }

    public function unInstallAgent(): void
    {
        \CAgent::RemoveModuleAgents($this->MODULE_ID);
    }

    /**
     * @return list<string>
     */
    private function splitSql(string $sql): array
    {
        $parts = preg_split('/;\s*[\r\n]+/', trim($sql)) ?: [];
        $queries = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '') {
                $queries[] = $part;
            }
        }

        return $queries;
    }
}
