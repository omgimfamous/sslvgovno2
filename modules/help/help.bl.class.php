<?php

abstract class HelpBase extends Module
{
    /** @var HelpModel */
    public $model = null;
    public $securityKey = '7a6479879566d66524eeea5e38589b28';

    public function init()
    {
        parent::init();
        $this->module_title = 'Помощь';
    }

    /**
     * Shortcut
     * @return Help
     */
    public static function i()
    {
        return bff::module('help');
    }

    /**
     * Shortcut
     * @return HelpModel
     */
    public static function model()
    {
        return bff::model('help');
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
        $baseUrl = static::urlBase(LNG, $dynamic) . '/help';
        switch ($key) {
            # главная
            case 'index':
                return $baseUrl . '/';
                break;
            # поиск вопросов
            case 'search':
                return $baseUrl . '/search/' . (!empty($opts) ? '?' . http_build_query($opts) : '');
                break;
            # список вопросов по категории
            case 'cat':
                return $baseUrl . '/' . (isset($opts['keyword']) ? $opts['keyword'] . '/' : '');
                break;
            # просмотр вопроса
            case 'view':
                return $baseUrl . '/' . mb_substr(mb_strtolower(func::translit($opts['title'])), 0, 100) . '-' . $opts['id'] . '.html';
                break;
        }
    }

    /**
     * Описание seo шаблонов страниц
     * @return array
     */
    public function seoTemplates()
    {
        return array(
            'pages'  => array(
                'listing'          => array(
                    't'      => 'Главная',
                    'list'   => false,
                    'i'      => true,
                    'macros' => array()
                ),
                'listing-category' => array(
                    't'       => 'Список в категории',
                    'list'    => false,
                    'i'       => true,
                    'macros'  => array(
                        'category'           => array('t' => 'Название категории (текущая)'),
                        'categories'         => array('t' => 'Название всех категорий'),
                        'categories.reverse' => array('t' => 'Название всех категорий<br />(обратный порядок)'),
                    ),
                    'inherit' => true,
                ),
                'search'           => array(
                    't'      => 'Поиск вопроса',
                    'list'   => true,
                    'i'      => true,
                    'macros' => array(
                        'query' => array('t' => 'Строка поиска'),
                    ),
                ),
                'view'             => array(
                    't'       => 'Просмотр вопроса',
                    'list'    => false,
                    'i'       => true,
                    'macros'  => array(
                        'title'              => array('t' => 'Заголовок вопроса'),
                        'textshort'          => array('t' => 'Краткое описание (до 150 символов)'),
                        'category'           => array('t' => 'Категория вопроса (текущая)'),
                        'categories'         => array('t' => 'Название всех категорий'),
                        'categories.reverse' => array('t' => 'Название всех категорий<br />(обратный порядок)'),
                    ),
                    'inherit' => true,
                ),
            ),
            'macros' => array(),
        );
    }

    /**
     * Инициализируем компонент bff\db\Publicator
     * @return bff\db\Publicator компонент
     */
    public function initPublicator()
    {
        $aSettings = array(
            'title'           => false,
            'langs'           => $this->locale->getLanguages(),
            'images_path'     => bff::path('help', 'images'),
            'images_path_tmp' => bff::path('tmp', 'images'),
            'images_url'      => bff::url('help', 'images'),
            'images_url_tmp'  => bff::url('tmp', 'images'),
            # photo
            'photo_sz_view'   => array('width' => 800),
            # gallery
            'gallery_sz_view' => array(
                'width'    => 800,
                'height'   => false,
                'vertical' => array('width' => false, 'height' => 400),
                'quality'  => 95,
                'sharp'    => array() // no sharp
            ),
        );

        return $this->attachComponent('publicator', new bff\db\Publicator($this->module_name, $aSettings));
    }

    /**
     * Формирование списка директорий/файлов требующих проверки на наличие прав записи
     * @return array
     */
    public function writableCheck()
    {
        return array_merge(parent::writableCheck(), array(
            bff::path('help', 'images') => 'dir', # изображения
            bff::path('tmp', 'images')  => 'dir', # tmp
        ));
    }
}