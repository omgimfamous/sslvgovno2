<?php

/**
 * Класс работы с данными авторизованного пользователя
 * @abstract
 */
abstract class User extends \bff\base\User
{
    /**
     * Получаем ID магазина
     * @return integer
     */
    public static function shopID()
    {
        return \bff::security()->getShopID();
    }
}