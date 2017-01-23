<?php

abstract class BlogBase extends Module
{
    /** @var BlogModel */
    public $model = null;
    public $securityKey = '75409335cd1659479b0d33fd462df2d9';

    public function init()
    {
        parent::init();
        $this->module_title = 'Блог';

        bff::autoloadEx(array(
                'BlogPostPreview' => array('app', 'modules/blog/blog.preview.php'),
            )
        );
    }

    /**
     * Shortcut
     * @return Blog
     */
    public static function i()
    {
        return bff::module('blog');
    }

    /**
     * Shortcut
     * @return BlogModel
     */
    public static function model()
    {
        return bff::model('blog');
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
        $baseUrl = static::urlBase(LNG, $dynamic) . '/blog';
        switch ($key) {
            # главная
            case 'index':
                return $baseUrl . '/';
                break;
            # список постов по категории
            case 'cat':
            {
                return $baseUrl . '/' . $opts['keyword'] . '/';
            }
            break;
            # список постов по тегу
            case 'tag':
            {
                return $baseUrl . '/tag/' . HTML::escape($opts['tag']) . '-' . $opts['id'];
            }
            break;
            # просмотр поста
            case 'view':
            {
                return $baseUrl . '/' . mb_substr(mb_strtolower(func::translit($opts['title'])), 0, 100) . '-' . $opts['id'] . '.html';
            }
            break;
        }
    }

    /**
     * Описание seo шаблонов страниц
     * @return array
     */
    public function seoTemplates()
    {
        $templates = array(
            'pages'  => array(
                'listing'          => array(
                    't'      => 'Список',
                    'list'   => true,
                    'i'      => true,
                    'macros' => array()
                ),
                'listing-category' => array(
                    't'       => 'Список в категории',
                    'list'    => true,
                    'i'       => true,
                    'macros'  => array(
                        'category' => array('t' => 'Название категория'),
                    ),
                    'inherit' => true,
                ),
                'listing-tag'      => array(
                    't'      => 'Список по тегу',
                    'list'   => true,
                    'i'      => true,
                    'macros' => array(
                        'tag' => array('t' => 'Тег'),
                    )
                ),
                'view'             => array(
                    't'       => 'Просмотр поста',
                    'list'    => false,
                    'i'       => true,
                    'macros'  => array(
                        'title'     => array('t' => 'Заголовок поста'),
                        'textshort' => array('t' => 'Краткое описание (до 150 символов)'),
                        'tags'      => array('t' => 'Список тегов'),
                    ),
                    'fields' => array(
                        'share_title'       => array(
                            't'    => 'Заголовок (поделиться в соц. сетях)',
                            'type' => 'text',
                        ),
                        'share_description' => array(
                            't'    => 'Описание (поделиться в соц. сетях)',
                            'type' => 'textarea',
                        ),
                        'share_sitename'    => array(
                            't'    => 'Название сайта (поделиться в соц. сетях)',
                            'type' => 'text',
                        ),
                    ),
                    'inherit' => true,
                ),
            ),
            'macros' => array(),
        );

        if (!static::categoriesEnabled()) {
            unset($templates['pages']['listing-category']);
            unset($templates['pages']['view']['category']);
        }
        if (!static::tagsEnabled()) {
            unset($templates['pages']['listing-tag']);
            unset($templates['pages']['view']['tags']);
        }

        return $templates;
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
            'images_path'     => bff::path('blog', 'images'),
            'images_path_tmp' => bff::path('tmp', 'images'),
            'images_url'      => bff::url('blog', 'images'),
            'images_url_tmp'  => bff::url('tmp', 'images'),
            // gallery
            'gallery_sz_view' => array(
                'width'    => 750,
                'height'   => false,
                'vertical' => array('width' => false, 'height' => 640),
                'quality'  => 95,
                'sharp'    => array()
            ), // no sharp
        );

        return $this->attachComponent('publicator', new bff\db\Publicator($this->module_name, $aSettings));
    }

    /**
     * Инициализация компонента работы с тегами
     * @return BlogPostTags
     */
    public function postTags()
    {
        static $i;
        if (!isset($i)) {
            require_once $this->module_dir . 'blog.post.tags.php';
            $i = new BlogPostTags();
        }

        return $i;
    }

    /**
     * Инициализация компонента работы с превью постов
     * @param integer $nPostID ID поста
     * @return BlogPostPreview объект
     */
    public function postPreview($nPostID = 0)
    {
        static $i;
        if (!isset($i)) {
            $i = new BlogPostPreview();
        }
        $i->setRecordID($nPostID);

        return $i;
    }

    /**
     * Включены ли категории
     * @return bool
     */
    public static function categoriesEnabled()
    {
        return (bool)config::sys('blog.categories', TYPE_BOOL);
    }

    /**
     * Включены ли теги
     * @return bool
     */
    public static function tagsEnabled()
    {
        return (bool)config::sys('blog.tags', TYPE_BOOL);
    }

    /**
     * Формирование списка директорий/файлов требующих проверки на наличие прав записи
     * @return array
     */
    public function writableCheck()
    {
        return array_merge(parent::writableCheck(), array(
            bff::path('blog', 'images') => 'dir', # изображения объявлений
            bff::path('tmp', 'images')  => 'dir', # tmp
        ));
    }

}