<?php

abstract class tpl extends \bff\base\tpl
{
    const PGN_COMPACT = 'pagination.compact';

    public static function itemPrice($nPrice, $nPriceCurrencyID, $nPriceEx = 0, $nViewCurrencyID = 0)
    {
        static $curr, $currDefault;
        if (!isset($curr)) {
            $curr = Site::model()->currencyData(false);
            $currDefault = config::sys('currency.default');
        }
        if (!isset($curr[$nPriceCurrencyID])) {
            $nPriceCurrencyID = $currDefault;
        }
        if (empty($nPrice) || $nPriceEx > BBS::PRICE_EX_MOD) {
            if ($nPriceEx & BBS::PRICE_EX_FREE) {
                return _t('bbs', 'Отдам даром');
            } else if ($nPriceEx & BBS::PRICE_EX_EXCHANGE) {
                return _t('bbs', 'Обмен');
            } else {
                return '';
            }
        }

        # конвертируем в необходимую валюту
        if (!empty($nViewCurrencyID)) {
            $nPrice = Site::currencyPriceConvert($nPrice, $nPriceCurrencyID, $nViewCurrencyID);
            $nPriceCurrencyID = $nViewCurrencyID;
        }

        if (fmod($nPrice, 1) > 0 && sizeof($curr) == 1) {
            $nPrice = number_format($nPrice, 2, ',', ($nPrice >= 1000 ? ' ' : ''));
        } else {
            $nPrice = number_format($nPrice, 0, '', ($nPrice >= 1000 ? ' ' : ''));
        }

        return $nPrice . (
        isset($curr[$nPriceCurrencyID]) ?
             ($curr[$nPriceCurrencyID]['is_sign'] ? '' : ' ') . $curr[$nPriceCurrencyID]['title_short']
            : '' );
    }

    static function date_format_pub($mDatetime, $sFormat = 'H:i, d.m.y')
    {
        if (is_string($mDatetime)) {
            $mDatetime = strtotime($mDatetime);
        }

        return date($sFormat, $mDatetime);
    }

    static function date_format3($sDatetime, $sFormat = false)
    {
        //get datetime
        if (!$sDatetime) {
            return '';
        }
        $date = func::parse_datetime($sDatetime);

        if ($sFormat !== false) {
            return date($sFormat, mktime((int)$date['hour'], (int)$date['min'], 0, (int)$date['month'], (int)$date['day'], (int)$date['year']));
        }

        //get now
        $now = array();
        list($now['year'], $now['month'], $now['day']) = explode(',', date('Y,m,d'));

        //дата позже текущей
        if ($now['year'] < $date['year']) {
            return '';
        }

        if ($now['year'] == $date['year'] && $now['month'] == $date['month']) {
            if ($now['day'] == $date['day']) {
                return _t('', 'сегодня') . " {$date['hour']}:{$date['min']}";
            } else {
                if ($now['day'] == $date['day'] - 1) {
                    return _t('', 'вчера') . " {$date['hour']}:{$date['min']}";
                }
            }
        }

        return "{$date['day']}.{$date['month']}.{$date['year']} в {$date['hour']}:{$date['min']}";
    }

    public static function datePublicated($mDatetime, $sDateFormat = 'Y-m-d H:i:s', $bTime = true, $sSeparator = '<br />')
    {
        static $now, $lng;
        if (!isset($now)) {
            $now = array();
            list($now['year'], $now['month'], $now['day']) = explode(',', date('Y,m,d'));
            $now = array_map('intval', $now);
            $lng = array(
                'today'     => _t('', 'Сегодня'),
                'yesterday' => _t('', 'Вчера'),
            );
        }
        if (!is_string($mDatetime)) {
            $mDatetime = date($sDateFormat, $mDatetime);
        }

        $date = date_parse_from_format($sDateFormat, $mDatetime);
        if (!empty($date['error_count'])) {
            return '';
        }

        if ($now['month'] == $date['month'] && $now['year'] == $date['year']) {
            if ($now['day'] == $date['day']) { # сегодня
                return $lng['today'] . ($bTime ? $sSeparator . sprintf('%01d:%02d', $date['hour'], $date['minute']) : '');
            } else {
                if ($now['day'] == $date['day'] - 1) { # вчера
                    return $lng['yesterday'] . ($bTime ? $sSeparator . sprintf('%01d:%02d', $date['hour'], $date['minute']) : '');
                }
            }
        }

        return $date['day'] . ' ' . \bff::locale()->getMonthTitle($date['month']) . ($now['year'] != $date['year'] ? $sSeparator . $date['year'] : '');
    }

    public static function getBreadcrumbs(array $aCrumbs = array(), $bActiveIsLink = false, $sTitleKey = 'title')
    {
        $aData = array(
            'crumbs'         => $aCrumbs,
            'active_is_link' => $bActiveIsLink,
            'title_key'      => $sTitleKey,
        );

        return View::renderTemplate($aData, 'breadcrumbs');
    }
}