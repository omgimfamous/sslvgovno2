<?php

require_once 'model.php';

abstract class SEOModuleBase extends Module
{
    /** @var SEOModelBase */
    public $model = null;
    protected $securityKey = '4c7ce37bb93111f217b7ae2x940f5b32';

    /** @var bool индексирование страницы роботом */
    protected $_robotsIndex = true;
    /** @var bool переход на страницу роботом */
    protected $_robotsFollow = true;
    /** @var array мета данные для вывода */
    protected $_metaData = array(
        'mtitle'       => '',
        'mkeywords'    => '',
        'mdescription' => '',
    );
    protected $_metaKeys = array(
        'mtitle',
        'mkeywords',
        'mdescription',
    );

    public function init()
    {
        parent::init();
        $this->module_title = 'SEO';
    }

    /**
     * @return SEO
     */
    public static function i()
    {
        return bff::module('seo');
    }

    /**
     * @return SEOModel
     */
    public static function model()
    {
        return bff::model('seo');
    }

    /**
     * Описание seo шаблонов страниц
     * @return array
     */
    public function seoTemplates()
    {
        $templates = array(
            'pages'  => array(),
            'macros' => array(),
        );

        if (static::landingPagesEnabled()) {
            $templates['pages']['landing-page'] = array(
                't'       => 'Посадочная страница',
                'macros'  => array(),
                'fields'  => static::landingPagesFields(),
            );
        }

        return $templates;
    }

    /**
     * Индексирование страницы роботом
     * @param boolean $index true - разрешить, false - запретить
     * @return boolean
     */
    public function robotsIndex($index = true)
    {
        return ($this->_robotsIndex = !empty($index));
    }

    /**
     * Переход на страницу роботом
     * @param boolean $follow true - разрешить, false - запретить
     */
    public function robotsFollow($follow = true)
    {
        $this->_robotsFollow = !empty($follow);
    }

    /**
     * Установка канонического URL
     * @param string $url
     * @param array $query доп. параметры
     * @param boolean $pageTranslated страница с уникальным переводом
     * @return string
     */
    public function canonicalUrl($url, array $query = array(), $pageTranslated = false)
    {
        $landing = static::landingPage();
        if ($landing !== false) {
            $url = rtrim(mb_substr($url, 0, mb_strpos($url, '{sitehost}') + 10), '/ ').$landing['landing_uri'];
            $this->robotsIndex(true);
        }
        if (!$this->_robotsIndex) {
            return $url;
        }

        $url = strval($url);

        # добавляем доп. параметры
        if (!empty($query)) {
            foreach ($query as $k => &$v) {
                if (empty($v) || ($k == 'page' && $v < 2)) {
                    unset($query[$k]);
                }
            }
            unset($v);
            if (!empty($query)) {
                $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query);
            }
        }

        # формируем полную ссылку
        if (strpos($url, '{site') !== false) {
            $scheme = (config::sys('https.canonical', false) ? 'https' : false);
            $languages = $this->locale->getLanguages();
            if (sizeof($languages) > 1) {
                $languageDefault = $this->locale->getDefaultLanguage();
                $this->_metaData['link-canonical'] = '<link rel="canonical" href="' . static::urlDynamic($url, array(), $languageDefault, $scheme) . '" />';
                foreach ($languages as $lng) {
                    if ($lng != $languageDefault) {
                        $this->_metaData['link-alternate-' . $lng] = '<link rel="alternate" hreflang="' . $lng . '" href="' . static::urlDynamic($url, array(), $lng, $scheme) . '" />';
                    }
                }

                return static::urlDynamic($url, array(), LNG, $scheme);
            }
            $url = static::urlDynamic($url, array(), LNG, $scheme);
        }

        $this->_metaData['link-canonical'] = '<link rel="canonical" href="' . $url . '" />';

        return $url;
    }

    /**
     * Вывод мета данных
     * @return string
     */
    public function metaRender()
    {
        $view =& $this->_metaData;
        # чистим незаполненные мета-данные
        foreach ($this->_metaKeys as $k) {
            if (empty($view[$k])) {
                unset($view[$k]);
            }
        }
        $view['language'] = '<meta http-equiv="Content-Language" content="' . LNG . '" />';
        $view['robots'] = '<meta name="robots" content="' . ($this->_robotsIndex ? 'index' : 'noindex') . ', ' . ($this->_robotsFollow ? 'follow' : 'nofollow') . '" />';

        return join("\r\n", $view) . "\r\n";
    }

    /**
     * Установка мета данных
     * @param string $type тип данных
     * @param string|mixed $data данные
     */
    public function metaSet($type, $data)
    {
        $data = trim(strval($data));

        switch ($type) {
            case 'mtitle':
            {
                $this->_metaData[$type] = '<title>' . mb_substr($data, 0, 1000) . '</title>';
            }
            break;
            case 'mkeywords':
            {
                $this->_metaData[$type] = '<meta name="keywords" lang="' . LNG . '" content="' . mb_substr(htmlspecialchars($data, ENT_QUOTES, 'UTF-8'), 0, 250) . '" />';
            }
            break;
            case 'mdescription':
            {
                $this->_metaData[$type] = '<meta name="description" lang="' . LNG . '" content="' . mb_substr(htmlspecialchars($data, ENT_QUOTES, 'UTF-8'), 0, 200) . '" />';
            }
            break;
        }
    }

    /**
     * Сохранение настроек шаблона мета данных страницы
     * @param string $moduleName название модуля страницы
     * @param string $pageKey ключ страницы
     * @param array $settings настройки
     * @return boolean
     */
    protected function metaTemplateSave($moduleName, $pageKey, array $settings)
    {
        if (empty($moduleName) || empty($pageKey)) {
            return false;
        }
        config::save($moduleName . '_meta_' . $pageKey, serialize($settings));
    }

    /**
     * Загрузка настроек шаблона мета данных страницы
     * @param string $moduleName название модуля страницы
     * @param string $pageKey ключ страницы
     * @return array
     */
    protected function metaTemplateLoad($moduleName, $pageKey)
    {
        # по-умолчанию
        $defaultData = array_fill_keys($this->locale->getLanguages(), '');
        $default = array('mtitle' => $defaultData, 'mkeywords' => $defaultData, 'mdescription' => $defaultData);
        if (empty($moduleName) || empty($pageKey)) {
            return $default;
        }

        # загружаем настройки
        $settings = config::get($moduleName . '_meta_' . $pageKey, '');
        $settings = func::unserialize($settings);
        if (empty($settings)) {
            $settings = $default;
        }

        return $settings;
    }

    /**
     * Подстановка макросов в мета-текст
     * @param string|array $text @ref текст
     * @param array $macrosData @ref данные для подстановки вместо макросов
     * @return string|array
     */
    public function metaTextPrepare(&$text, array &$macrosData = array())
    {
        if (empty($text)) {
            return $text;
        }

        # подготавливаем макросы для замены
        $replace = array('{site.title}' => config::sys('site.title'));
        foreach ($macrosData as $k => $v) {
            if ($k == 'page' && is_numeric($v)) {
                $v = ($v > 1 ? _t('pgn', ' - страница [page]', array('page' => $v)) : '');
                $replace[' {' . $k . '}'] = $replace['{' . $k . '}'] = $v;
            } else {
                if ($v == '') {
                    foreach (array(' ', ' - ', ' | ', ', ', ': ') as $prefix) {
                        $replace[$prefix.'{' . $k . '}'] = $v;
                    }
                } else {
                    $replace['{' . $k . '}'] = $v;
                }
                $replace['{' . $k . '}'] = $v;
            }
        }
        if (is_string($text)) {
            $text = strtr($text, $replace);
        } else {
            foreach ($text as &$v) {
                $v = strtr($v, $replace);
            }
        }

        return $text;
    }

    /**
     * Формирование посадочной страницы
     * @param string|boolean $request:
     *  string - URI текущего запроса
     *  false  - вернуть данные о текущей посадочной странице
     * @return mixed
     */
    public static function landingPage($request = false)
    {
        # Посадочные страницы не используются (выключены)
        if ( ! static::landingPagesEnabled()) {
            return false;
        }

        static $page;
        if (is_string($request)) {
            # Выполняем поиск посадочной страницы по URI текущего запроса
            $request = '/'.ltrim($request, '/ ');
            $page = static::model()->landingpageDataByURI($request);
            if (empty($page) && !empty($request)) {
                # Дополняем / убираем завершающий "/"
                $request = (mb_substr($request, -1) === '/' ? mb_substr($request, 0, -1) : $request.'/');
                $page = static::model()->landingpageDataByURI($request);
            }
            if (!empty($page)) {
                return $page['original_uri'];
            } else {
                # Сбрасываем объявленную ранее посадочную страницу
                $page = false;
                return false;
            }
        }

        # Посадочная страница не была объявлена
        if (empty($page)) {
            return false;
        }

        return $page;
    }

    /**
     * Включено ли использование посадочных страниц
     * @return bool
     */
    public static function landingPagesEnabled()
    {
        return config::sys('seo.landing.pages.enabled', TYPE_BOOL);
    }

    /**
     * Доп. поля для посадочных страниц
     * @return array
     */
    public static function landingPagesFields()
    {
        $fields = config::sys('seo.landing.pages.fields', array());
        if (!empty($fields) && is_array($fields)) {
            return $fields;
        } else {
            return array();
        }
    }
}