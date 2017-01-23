<?php

/**
 * Базовый класс кеширования
 * @abstract
 * @version 0.22
 * @modified 16.mar.2015
 */

abstract class Cache
{
    /**
     * Префикс кеш ключей уникальный для каждого хоста
     * @var string
     */
    public $keyPrefix = SITEHOST;
    /**
     * Префикс группы кеш ключей
     * @var string
     */
    protected $groupName = 'default';

    public function init()
    {
    }

    /**
     * Инициализация кеш-объекта для указанного кеш-драйвера
     * @param string $sGroupName имя кеш группы (например: название модуля)
     * @param string $sDriver имя драйвера кеша
     * @param mixed $mInit false или array()
     * @return Cache объект
     */
    public static function &singleton($sGroupName, $sDriver = 'apc', $mInit = true)
    {
        static $instances = array();

        switch ($sDriver) {
            case 'apc':
            {
                if (!extension_loaded('apc')) {
                    # если нет возможность использовать APC
                    # переключаемся на Lite
                    $sDriver = 'file';
                }
            }
            break;
            case 'xcache':
            {
                if (!extension_loaded('xcache')) {
                    # если нет возможность использовать XCache
                    # переключаемся на Lite
                    $sDriver = 'file';
                }
            }
            break;
        }

        $signature = serialize(array($sDriver, $sGroupName, $mInit));
        if (empty($instances[$signature])) {
            $instances[$signature] = Cache::factory($sDriver);
            if ($mInit !== false) {
                $instances[$signature]->init($mInit);
            }
        }

        $instances[$signature]->setGroup($sGroupName);

        return $instances[$signature];
    }

    /**
     * Доступные драйвера: apc, eaccelerator, lite
     * @param string $sDriver имя cache-драйвера
     * @return Cache объект
     */
    public static function factory($sDriver)
    {
        if (empty($sDriver) || $sDriver == 'none') {
            return null;
        }

        if (file_exists(PATH_CORE . 'cache' . DS . 'cache.' . $sDriver . '.php')) {
            include_once PATH_CORE . 'cache' . DS . 'cache.' . $sDriver . '.php';
        }

        $class = 'Cache' . $sDriver;

        return new $class();
    }

    protected function generateUniqueKey($key)
    {
        return $this->groupName . md5($this->keyPrefix . $key);
    }

    /**
     * Возвращает значение из кеша по специальному ключу.
     * @param string $id ключ
     * @return mixed значение, false - если в кеше нет записи по заданному ключу, истек срок хранения или изменилась зависимость.
     */
    public function get($id = null)
    {
        if (($value = $this->getValue($this->generateUniqueKey($id))) !== false) {
            $data = @unserialize($value);
            if (!is_array($data)) {
                return false;
            }
            if (!($data[1] instanceof \ICacheDependency) || !$data[1]->getHasChanged()) {
                return $data[0];
            }
        }

        return false;
    }

    /**
     * Возвращает несколько значений из кеша по ключам.
     * @param array $ids список ключей
     * @return array список значений: пары (ключ, значение).
     * false - если записи нет или истек срок её храниения
     */
    public function mget($ids)
    {
        $uniqueIDs = array();
        $results = array();
        foreach ($ids as $id) {
            $uniqueIDs[$id] = $this->generateUniqueKey($id);
            $results[$id] = false;
        }

        $values = $this->getValues($uniqueIDs);
        foreach ($uniqueIDs as $id => $uniqueID) {
            if (!isset($values[$uniqueID])) {
                continue;
            }

            $data = unserialize($values[$uniqueID]);
            if (is_array($data) && (!($data[1] instanceof \ICacheDependency) || !$data[1]->getHasChanged())) {
                $results[$id] = $data[0];
            }
        }

        return $results;
    }

    /**
     * Сохраняем значение по ключу.
     * Заменяем(значение и срок хранения) если значение под данным ключем уже существует.
     * @param string $id ключ
     * @param mixed $value значение
     * @param integer $expire срок хранения в секундах. 0 - без ограничения срока хранения.
     * @param \ICacheDependency $dependency зависимость записи в кеше. Если зависимость меняется, запись помечается как "невалидная".
     * @return boolean true - значение успешно сохранено в кеш, false - нет.
     */
    public function set($id, $value, $expire = 0, $dependency = null)
    {
        if ($dependency !== null) {
            $dependency->evaluateDependency();
        }

        $data = array($value, $dependency);

        return $this->setValue($this->generateUniqueKey($id), serialize($data), $expire);
    }

    /**
     * Сохраняем значение по ключу, если записи с таким ключем еще нет.
     * @param string $id ключ
     * @param mixed $value значение
     * @param integer $expire срок хранения в секундах. 0 - без ограничения срока хранения.
     * @param \ICacheDependency $dependency зависимость записи в кеше. Если зависимость меняется, запись помечается как "невалидная".
     * @return boolean true - значение успешно сохранено в кеш, false - нет.
     */
    public function add($id, $value, $expire = 0, $dependency = null)
    {
        if ($dependency !== null) {
            $dependency->evaluateDependency();
        }

        $data = array($value, $dependency);

        return $this->addValue($this->generateUniqueKey($id), serialize($data), $expire);
    }

    /**
     * Проверяем наличие записи в кеше по ключу.
     * @param string $id ключ
     * @return boolean|mixed
     */
    public function exists($id)
    {
        return $this->existsValue($this->generateUniqueKey($id));
    }

    /**
     * Удаляем запись из кеша по ключу.
     * @param string $id ключ
     * @return boolean если не возникло ошибок в процессе удаления
     */
    public function delete($id)
    {
        return $this->deleteValue($this->generateUniqueKey($id));
    }

    /**
     * Удаляем все записи из кеша.
     * Для имплементации в классах наследниках.
     * @param string $sGroupName имя группы
     */
    public function flush($sGroupName = false)
    {
    }

    /**
     * Возвращает значение из кеша по ключу.
     * Для имплементации в классах наследниках.
     * @param string $key уникальный ключ
     * @return mixed значение, false - если в кеше нет записи по заданному ключу, истек срок хранения.
     */
    protected abstract function getValue($key);

    /**
     * Возвращает несколько значений из кеша по ключам.
     * Для имплементации в классах наследниках.
     * @param array $keys список ключей
     * @return array список значений: ключ=>значение.
     */
    protected function getValues($keys)
    {
        $results = array();
        foreach ($keys as $key) {
            $results[$key] = $this->getValue($key);
        }

        return $results;
    }

    /**
     * Сохраняем значение по ключу.
     * Для имплементации в классах наследниках.
     * @param string $key уникальный ключ
     * @param string $value значение
     * @param integer $expire срок хранения в секундах. 0 - без ограничения срока хранения.
     * @return boolean true - значение успешно сохранено в кеш, false - нет.
     */
    protected abstract function setValue($key, $value, $expire);

    /**
     * Сохраняем значение по ключу, если записи с таким ключем еще нет.
     * Для имплементации в классах наследниках.
     * @param string $key ключ
     * @param mixed $value значение
     * @param integer $expire срок хранения в секундах. 0 - без ограничения срока хранения.
     * @return boolean true - значение успешно сохранено в кеш, false - нет.
     */
    protected abstract function addValue($key, $value, $expire);

    /**
     * Проверяем наличие записи в кеше по ключу.
     * Для имплементации в классах наследниках.
     * @param string $key ключ
     * @return boolean|mixed
     */
    protected abstract function existsValue($key);

    /**
     * Удаляем запись из кеша по ключу.
     * Для имплементации в классах наследниках.
     * @param string $key ключ
     * @return boolean если не возникло ошибок в процессе удаления
     */
    protected abstract function deleteValue($key);

    /**
     * Помечаем кеш группу.
     * @param string $sGroupName имя группы
     */
    public function setGroup($sGroupName)
    {
        $this->groupName = $sGroupName;
    }

    /**
     * Получаем данные о текущей кеш группе.
     * @return mixed
     */
    public function getGroup()
    {
        return array('name' => $this->groupName);
    }

}