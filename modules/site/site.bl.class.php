<?php

abstract class SiteBase extends SiteModule
{
    /** @var SiteModel */
    public $model = null;

    public function init()
    {
        parent::init();
    }

    public static function currencyOptions($nSelectedID, $mEmpty = false)
    {
        $aCurrency = static::model()->currencyData(false);

        return HTML::selectOptions($aCurrency, $nSelectedID, $mEmpty, 'id', 'title_short');
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
        $base = static::urlBase(LNG, $dynamic);
        switch ($key) {
            # главная страница
            case 'index':
                return $base . '/';
                break;
            # главная страница
            case 'index-geo':
                return Geo::url($opts, $dynamic);
                break;
            # статическая страница
            case 'page':
                return $base . '/' . $opts['filename'] . static::$pagesExtension;
                break;
            # карта сайта
            case 'sitemap':
                return $base . '/sitemap/';
                break;
            # страница "Услуги"
            case 'services':
                return $base . '/services/';
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
            'pages' => array(
                'index'         => array(
                    't'      => 'Главная страница',
                    'i'      => true,
                    'macros' => array(),
                    'fields' => array(
                        'titleh1' => array(
                            't'      => 'Приветствие (заголовок H1)',
                            'type'   => 'text',
                            'before' => true,
                        ),
                        'seotext' => array(
                            't'    => 'SEO текст',
                            'type' => 'wy',
                        )
                    ),
                ),
                'index-region'  => array(
                    't'      => 'Главная страница (регион)',
                    'i'      => true,
                    'macros' => array(
                        'region' => array('t' => 'Регион поиска'),
                    ),
                    'fields' => array(
                        'titleh1' => array(
                            't'      => 'Приветствие (заголовок H1)',
                            'type'   => 'text',
                            'before' => true,
                        ),
                        'seotext' => array(
                            't'    => 'SEO текст',
                            'type' => 'wy',
                        )
                    ),
                ),
                'page-view'     => array(
                    't'       => 'Статические страницы',
                    'i'       => true,
                    'inherit' => true,
                    'macros'  => array(
                        'title' => array('t' => 'Заголовок страницы'),
                    ),
                ),
                'sitemap'       => array(
                    't'      => 'Карта сайта',
                    'i'      => true,
                    'macros' => array(),
                    'fields' => array(
                        'seotext' => array(
                            't'    => 'SEO текст',
                            'type' => 'wy',
                        )
                    ),
                ),
                'services'      => array(
                    't'      => 'Страница "Услуги"',
                    'i'      => true,
                    'macros' => array(),
                ),
                'contacts-form' => array(
                    't'      => 'Форма контактов',
                    'i'      => true,
                    'macros' => array(),
                ),
            ),
        );
    }

    /**
     * Формирование списка директорий/файлов требующих проверки на наличие прав записи
     * @return array
     */
    public function writableCheck()
    {
        return array_merge(parent::writableCheck(), array(
            PATH_PUBLIC.'files' => 'dir', # sitemap.xml
            PATH_PUBLIC.'files'.DS.'sitemap.xml' => 'file-e', # файл sitemap.xml
        ));
    }
}