<?php

require_once 'model.php';

abstract class SiteModuleBase extends Module
{
    /** @var SiteModelBase */
    public $model = null;
    protected $securityKey = 'bc7ce37bb936e4f217b7ae2a940f5b3e';
    # Pages
    static $pagesPath = '';
    static $pagesExtension = '.html';

    public function init()
    {
        parent::init();
        $this->module_title = 'Настройки сайта';
        static::$pagesPath = bff::path('pages');

        if (static::pagesPublicatorEnabled()) {
            $this->model->langPage['content_publicator_search'] = TYPE_NOTAGS;
        }
    }

    /**
     * @return Site
     */
    public static function i()
    {
        return bff::module('site');
    }

    /**
     * @return SiteModel
     */
    public static function model()
    {
        return bff::model('site');
    }

    public function getCounters()
    {
        return $this->model->countersView();
    }

    /**
     * Получаем данные об основной валюте
     * @param string|boolean $mValueKey ключ требуемых данных, false - все данные
     * @return mixed
     */
    public static function currencyDefault($mValueKey = 'title_short')
    {
        if ($mValueKey == 'id') {
            return config::sys('currency.default');
        }

        return static::currencyData(0, $mValueKey);
    }

    /**
     * Получаем данные о необходимой валюте
     * @param int $nCurrencyID ID валюты, если 0 - возвращаем данные об основной валюте
     * @param string|boolean $mValueKey ключ требуемых данных, false - все данные
     * @return mixed
     */
    public static function currencyData($nCurrencyID = 0, $mValueKey = false)
    {
        $aData = static::model()->currencyData(false);
        if (empty($nCurrencyID)) {
            $nCurrencyID = config::sys('currency.default');
        }
        if (!isset($aData[$nCurrencyID])) {
            return false;
        }

        return ($mValueKey !== false && isset($aData[$nCurrencyID][$mValueKey]) ? $aData[$nCurrencyID][$mValueKey] : $aData[$nCurrencyID]);
    }

    /**
     * Конвертация цены в требуемую валюту, по-курсу
     * @param integer $nPrice цена
     * @param integer $nFromID ID валюты, в которой указана цена
     * @param integer $nToID ID валюты, в которую необходимо конвертировать цену
     * @return float
     */
    public static function currencyPriceConvert($nPrice, $nFromID, $nToID = 0)
    {
        if ($nFromID == $nToID # цена уже указана в требуемой валюте
            || $nPrice <= 0 # цена <= 0
            || !($nDefaultID = config::sys('currency.default'))
        ) { # ID валюты по-умолчанию указан некорректно
            return $nPrice;
        }

        # корректируем ID валют
        if (!$nFromID) {
            $nFromID = $nDefaultID;
        }
        if (!$nToID) {
            $nToID = $nDefaultID;
        }
        if ($nFromID == $nToID) {
            return $nPrice;
        }

        # конвертируем в основную валюту по курсу
        $nPriceDefault = ($nFromID == $nDefaultID ? $nPrice : round($nPrice * floatval(static::currencyData($nFromID, 'rate'))));
        if ($nToID == $nDefaultID) {
            return $nPriceDefault;
        } else {
            # конвертируем в требуемую валюту
            $nRate = floatval(static::currencyData($nToID, 'rate'));

            return ($nRate > 0 ? $nPriceDefault / $nRate : $nPriceDefault);
        }
    }

    /**
     * Конвертация цены в основную валюту, по-курсу
     * @param integer $nPrice цена
     * @param integer $nPriceCurrencyID ID валюты, в которой указана цена
     * @return float
     */
    public static function currencyPriceConvertToDefault($nPrice, $nPriceCurrencyID)
    {
        return static::currencyPriceConvert($nPrice, $nPriceCurrencyID, 0);
    }

    /**
     * Инициализация компонента bff\db\Publicator для статических страниц
     * @return bff\db\Publicator
     */
    public function pagesPublicator()
    {
        $aSettings = array(
            'title'           => false,
            'langs'           => $this->locale->getLanguages(),
            'images_path'     => bff::path('pages', 'images'),
            'images_path_tmp' => bff::path('tmp', 'images'),
            'images_url'      => bff::url('pages', 'images'),
            'images_url_tmp'  => bff::url('tmp', 'images'),
            'photo_sz_view'   => array('width' => 960),
            'images_original' => true,
            // gallery
            'gallery_sz_view' => array(
                'width'    => 960, 'height' => false,
                'vertical' => array('width' => false, 'height' => 640),
                'quality'  => 95,
                'sharp'    => array()
            ), // no sharp
        );

        if (static::pagesPublicatorEnabled()) {
            $configSettings = config::sys('pages.publicator.settings', array());
            if (!empty($configSettings) && is_array($configSettings)) {
                $aSettings = array_merge($aSettings, $configSettings);
            }
        }

        return $this->attachComponent('publicator', new bff\db\Publicator($this->module_name, $aSettings));
    }

    /**
     * Использовать Publicator для статических страниц
     * @return boolean|integer
     */
    public static function pagesPublicatorEnabled()
    {
        return config::sys('pages.publicator', false);
    }

    /**
     * Защита от спама (частой отправки сообщений / выполнения действий)
     * @param string $key ключ выполняемого действия
     * @param integer $timeout допустимая частота выполнения действия, в секундах
     * @param boolean $setError устанавливать ошибку
     * @return boolean true - частота отправки превышает допустимый лимит, false - все ок
     */
    public function preventSpam($key, $timeout = 20, $setError = true)
    {
        $timeout = intval($timeout);
        if ($timeout <= 0) {
            return false;
        }

        $last = $this->model->requestGet($key, User::id(), Request::remoteAddress(), false);
        if ($last > 0 && ((BFF_NOW - $last) < $timeout)) {
            if ($setError) {
                if ($timeout <= 70) {
                    $this->errors->set(_t('', 'Повторите попытку через одну минуту'));
                } else {
                    $this->errors->set(_t('', 'Повторите попытку через несколько минут'));
                }
            }

            return true;
        } else {
            $this->model->requestSet($key, User::id(), Request::remoteAddress());
        }

        return false;
    }

    /**
     * Защита от спама на основе допустимого кол-ва повторов выполненного действия
     * В случае привышения этого кол-ва включаем режим ожидание ($timeout)
     * @param string $key ключ выполняемого действия
     * @param integer $limit допустимое кол-во повторов
     * @param integer $timeout таймаут ожидания (cooldown) при достижении лимита попыток, в секундах
     * @param boolean $setError устанавливать ошибку
     * @return boolean true - достигнут лимит повторов, false - все ок
     */
    public function preventSpamCounter($key, $limit, $timeout = 20, $setError = true)
    {
        $limit = intval($limit);
        $timeout = intval($timeout);
        if ($limit <= 0 || $timeout <= 0) {
            return false;
        }

        $userID = User::id();
        $ipAddress = Request::remoteAddress(false, true);
        $last = $this->model->requestGet($key, $userID, $ipAddress, true);

        # первое выполнение действия
        if (empty($last)) {
            $this->model->requestSet($key, $userID, $ipAddress, 1);
            return false;
        }

        $filter = array('user_action' => $key);
        if ($userID) {
            $filter['user_id'] = $userID;
        } else {
            $filter['user_ip'] = $ipAddress;
        }
        $counter = intval($last['counter']);
        if ($counter < $limit) {
            # повтор: лимит не достигнут
            $this->model->requestUpdate($filter, array(
                'counter' => $counter + 1,
                'created' => $this->db->now(),
            ));
            return false;
        } else if ($counter === $limit) {
            # повтор: лимит достигнут
            # пропускаем + помечаем период ожидания для последующих попыток
            $this->model->requestUpdate($filter, array(
                'counter' => $counter + 1,
                'created' => date('Y-m-d H:i:s', strtotime('+ '.$timeout.' seconds')),
            ));
            return false;
        } else {
            # период ожидания: просим подождать
            if (strtotime($last['created']) > BFF_NOW) {
                if ($setError) {
                    if ($timeout <= 70) {
                        $this->errors->set(_t('', 'Повторите попытку через одну минуту'));
                    } else {
                        $this->errors->set(_t('', 'Повторите попытку через несколько минут'));
                    }
                }
                return true;
            } else {
                # завершаем период ожидания, сбрасываем счетчик попыток
                $this->model->requestUpdate($filter, array(
                    'counter' => 1,
                    'created' => $this->db->now(),
                ));
                return false;
            }
        }
    }

    /**
     * Реализация работы с сайтом в выключенном режиме
     * @param string $step тип действия
     * @return string
     */
    public static function offlineIgnore($step = 'validate')
    {
        $key = 'offline';
        $secret = hash('sha256', config::sys('site.rand1', 'S0$E25Uf2$8Dv(nG78$U@Z&7GM3^x!', TYPE_STR).config::sys('site.title'));
        switch ($step)
        {
            case 'generate-url': # сгенерировать ссылку "просмотра"
                return bff::urlBase().'?'.$key.'='.$secret;
                break;
            case 'validate':
                if (bff::$class === 'bills' || Request::isPOST()) {
                    return true;
                }
                if (!empty($_GET[$key]) && bff::input()->get($key, TYPE_NOTAGS) === $secret) {
                    Request::setCOOKIE($key, $secret);
                    return true;
                }
                if (!empty($_COOKIE[$key]) && bff::input()->cookie($key, TYPE_NOTAGS) === $secret) {
                    return true;
                }
                return false;
                break;
        }
    }

    /**
     * Обработка копирования данных локализации
     * @param string $from ключ языка
     * @param string $to ключ языка
     */
    public function onLocaleDataCopy($from, $to)
    {
        # настройки сайта
        $configUpdate = array();
        $fromPostfixLen = mb_strlen('_' . $from);
        foreach (config::$data as $k => $v) {
            if (mb_strrpos($k, '_' . $from) === mb_strlen($k) - $fromPostfixLen) {
                $configUpdate[mb_substr($k, 0, -$fromPostfixLen) . '_' . $to] = $v;
            }
        }
        config::save($configUpdate);
    }

    /**
     * Формирование списка директорий/файлов требующих проверки на наличие прав записи
     * @return array
     */
    public function writableCheck()
    {
        $dirs = array(
            config::file('site', true) => 'file', # настройки сайта
            static::$pagesPath         => 'dir', # статические страницы
            bff::path('tmp', 'images') => 'dir', # временная директория загрузки изображений
        );
        $tmp1 = ini_get('upload_tmp_dir');
        if (!empty($tmp1)) {
            $dirs[$tmp1] = array('type'=>'dir-only', 'title'=>'директория временных файлов при загрузке'); # временная директория загрузки файлов
        }
        $tmp2 = sys_get_temp_dir();
        if (!empty($tmp2) && $tmp1 !== $tmp2) {
            $dirs[$tmp2] = array('type'=>'dir-only', 'title'=>'директория временных файлов'); # директория tmp файлов
        }
        if (static::pagesPublicatorEnabled()) {
            $dirs[bff::path('pages', 'images')] = 'dir'; # статические страницы: публикатор
        }
        # файлы локализации
        $dirs[bff::locale()->gt_Domain('path')] = 'file'; # domain.php
        foreach (bff::locale()->getLanguages(true) as $lng) {
            $dirs[bff::locale()->gt_Path($lng)] = 'dir-only'; # файлы переводов
        }
        return array_merge(parent::writableCheck(), $dirs);
    }
}