<?php namespace bff;

/**
 * Базовый абстрактный класс Singleton
 * @abstract
 * @version 0.11
 * @modified 14.декабря.2012
 */

abstract class Singleton
{
    private $_initialized = false;

    protected function __construct()
    {
    }

    public function init()
    {
        if ($this->getIsInitialized()) {
            return false;
        }

        $this->setIsInitialized();

        return true;
    }

    public function getIsInitialized()
    {
        return $this->_initialized;
    }

    public function setIsInitialized()
    {
        $this->_initialized = true;
    }

    /**
     * Делаем возможным только один экземпляр класса
     * @core-doc
     * @return self
     */
    public static function i()
    {
        static $oInstance = array();
        $className = get_called_class();
        if (!isset($oInstance[$className])) {
            $oInstance[$className] = new $className();
        }

        return $oInstance[$className];
    }

    /**
     * Блокируем копирование/клонирование объекта
     */
    final private function __clone()
    {
    }
}