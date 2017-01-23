<?php

class Sitemap extends SitemapBase
{
    /**
     * Построение меню
     * @param $sKey ключ меню, например: 'main', 'footer'
     */
    public static function view($sKey)
    {
        static $cache;

        if (!isset($cache)) {
            $self = static::i();
            $self->buildMenu(true, 'none');
            $cache = $self->menu;
            if (!bff::shopsEnabled() && isset($cache['main']['sub']['shops'])) {
                unset($cache['main']['sub']['shops']);
            }
            if (!bff::servicesEnabled() && isset($cache['main']['sub']['services'])) {
                unset($cache['main']['sub']['services']);
            }
        }

        return (!empty($cache[$sKey]['sub']) ? $cache[$sKey]['sub'] : array());
    }

}