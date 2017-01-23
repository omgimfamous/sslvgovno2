<?php

/**
 * Настройки доступных языков
 * @example:
 *  'ключ языка' => array(
 *      'locale' => название локали в системе (аналогичное названию директории в files/locale)
 *      'month'  => название месяцев на данном языке
 *  )
 * @link: http://htmlbook.ru/html/value/lang
 */

return array(
    # Английский - США
    'en' => array(
        'locale'=>'en_US',
        'month'=>array(0=>'','january','february','march','april','may','june','july','august','september','october','november','december'),
    ),
    # Русский - Россия
    'ru' => array(
        'locale'=>'ru_RU',
        'month'=>array(0=>'','января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'),
    ),
    # Украинский - Украина
    'uk' => array(
        'locale'=>'uk_UA',
        'month'=>array(0=>'','січня','лютого','березня','квітня','травня','червня','липня','серпня','вересня','жовтня','листопада','грудня'),
    ),
    # Грузинский - Грузия
    'ka' => array(
        'locale'=>'ka_GE',
        'month'=>array(0=>'','იანვარი','თებერვალი','მარტი','აპრილი','მაისი','ივნისი','ივლისი','აგვისტო','სექტემბერი','ოქტომბერი','ნოემბერი','დეკემბერი'),
    ),
    # Немецкий - Германия
    'de' => array(
        'locale'=>'de_DE',
        'month'=>array(),
    ),
    # Белорусский - Белоруссия
    'be' => array(
        'locale'=>'be_BY',
        'month'=>array(),
    ),
    # Казахский - Казахстан
    'kk' => array(
        'locale'=>'kk_KZ',
        'month'=>array()
    ),
    # Киргизский - Киргизия
    'ky' => array(
        'locale'=>'ky_KG',
        'month'=>array()
    ),
    # Азербайджанский - Азербайджан
    'az' => array(
        'locale'=>'az_AZ',
        'month'=>array(0=>'','yanvar','fevral','mart','aprel','may','iyun','iyul','avqust','sentyabr','oktyabr','noyabr','dekabr'),
    ),
    # Польский - Польша
    'pl' => array(
        'locale'=>'pl_PL',
        'month'=>array()
    ),
);