<?php

abstract class BillsBase extends BillsModule
{
    /** @var BillsModel */
    public $model = null;

    public function init()
    {
        parent::init();

        # включаем доступные системы оплаты:
        $this->psystemsAllowed = array(
            self::PS_W1,
        );

        /**
         * Настройки доступных систем оплаты указываются в файле [/config/sys.php]
         * Полный список доступных настроек указан в BillsModuleBase::init методе [/bff/modules/bills/base.php]
         * Формат: 'bills.[ключ системы оплаты].[ключ настройки]'
         * Пример: 'bills.robox.test' - тестовый режим системы оплаты Robokassa
         *
         * URL для систем оплат:
         * Result:  http://example.com/bill/process/(robox|wm)
         * Success: http://example.com/bill/success
         * Fail:    http://example.com/bill/fail
         */
    }

    /**
     * Формирование URL
     * @param string $key ключ
     * @param array $opts доп. параметры
     * @param boolean $dynamic динамическая ссылка
     * @return string
     */
    public static function url($key, array $opts = array(), $dynamic = false)
    {
        switch ($key) {
            # история операций (список завершенных счетов)
            case 'my.history':
                return static::urlBase(LNG, $dynamic) . '/cabinet/bill' . (!empty($opts) ? '?' . http_build_query($opts) : '');
                break;
            # пополнение счета
            case 'my.pay':
                return static::url('my.history', $opts + array('pay' => 1), $dynamic);
                break;
        }
    }

    public static function getPaySystems($bBalanceUse = false)
    {
        $logoUrl = SITEURL_STATIC . '/img/ps/';
        $aData = array(
            'robox'    => array(
                'id'           => self::PS_ROBOX,
                'way'          => '',
                'logo_desktop' => $logoUrl . 'robox.png',
                'logo_phone'   => $logoUrl . 'robox.png',
                'title'        => _t('bills', 'Robokassa'),
                'currency_id'  => 2, # рубли
            ),
            'wm'       => array(
                'id'           => self::PS_WM,
                'way'          => 'wmz',
                'logo_desktop' => $logoUrl . 'wm.png',
                'logo_phone'   => $logoUrl . 'wm.png',
                'title'        => _t('bills', 'Webmoney'),
                'currency_id'  => 3, # доллары
            ),
            'terminal' => array(
                'id'           => self::PS_W1,
                'way'          => 'terminal',
                'logo_desktop' => $logoUrl . 'w1.png',
                'logo_phone'   => $logoUrl . 'w1.png',
                'title'        => _t('bills', 'Терминал'),
                'currency_id'  => 2, # рубли
            ),
        );

        if ($bBalanceUse) {
            $aData = array(
                    'balance' => array(
                        'id'           => self::PS_UNKNOWN,
                        'way'          => '',
                        'logo_desktop' => SITEURL_STATIC . '/img/do-logo.png',
                        'logo_phone'   => SITEURL_STATIC . '/img/do-logo.png',
                        'title'        => _t('bills', 'Счет [name]', array('name' => config::sys('site.title'))),
                    )
                ) + $aData;
        }

        return $aData;
    }
}