<?php

/**
 * Компонент управляющий загрузкой / сохранением / удалением нескольких изображений
 * Для хранения используются поля в таблице записи
 * @abstract
 * @version 0.1a
 * @modified 22.мая.2013
 */
abstract class CImagesUploaderField extends CImagesUploader
{
    /** @var integer ID пользователя */
    protected $userID = 0;

    /** @var string Название таблицы для хранения данных о записи */
    protected $tableRecords = '';
    /** @var string Название id-столбца в таблице для хранения данных о записи */
    protected $tableRecords_id = 'id';

    /** @var boolean флаг, определяющий работу с полем fFav */
    protected $useFav = true;

    /**
     * Размеры fav-изображений для кеширование полного URL пути к изображениям
     * Массив префиксов размеров в формате: array(sizePrefix=>fieldName, sizePrefix=>fieldName, ...)
     * @var array
     */
    protected $sizesFav = array();

    /**
     * Максимально доступное кол-во изображений у одной записи
     * 0 - неограничено
     * @var integer
     */
    protected $limit = 5;
}