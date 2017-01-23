<?php namespace bff\db;

// ORM
class Table
{
    public $_id;
    /** @var Database объект */
    private $db;
    private $table, $pkeys, $fields, $types, $fieldsV, $mod, $empty, $cond, $seq, $ofs;

    function factory($row)
    {
        $self = get_class($this);
        $obj = new $self($this->table, $this->db);
        foreach ($row as $field => $val) {
            if (array_key_exists($field, $this->fields)) {
                $obj->fields[$field] = $val;
                if ($this->pkeys &&
                    array_key_exists($field, $this->pkeys)
                ) {
                    $obj->pkeys[$field] = $val;
                }
            } else {
                $obj->fieldsV[$field] = array($this->fieldsV[$field][0], $val);
            }
            if ($obj->empty && $val) {
                $obj->empty = false;
            }
        }

        return $obj;
    }

    /**
     * Возвращаем данные о текущей записи в виде массива
     * @return array
     */
    function cast()
    {
        return $this->fields;
    }

    /**
     * Обвертка для SELECT запроса
     * @param string $fields поля
     * @param mixed $cond условия
     * @param string $group группировка
     * @param string $seq сортировка
     * @param mixed(string|integer) $limit лимит
     * @param int $ofs offset
     * @param boolean $obj конвертировать полученный результат в массив объектов
     * @returns array
     */
    function select($fields = null, $cond = null, $group = null, $seq = null, $limit = 0, $ofs = 0, $obj = false)
    {
        $fields = (!empty($fields) && is_array($fields) ? join(', ', $fields) : ($fields ? $fields : '*'));

        if (!empty($limit) && is_string($limit)) {
            /* $limit = 'LIMIT 10 OFFSET 5'; */
            $ofs = '';
        } else {
            $limit = ($limit ? ' LIMIT ' . $limit : '');
            $ofs = ($ofs ? (' OFFSET ' . $ofs) : '');
        }

        $rows = is_array($cond) ?
            $this->db->exec(
                'SELECT ' . $fields . ' FROM ' . $this->table .
                ($cond ? (' WHERE ' . $cond[0]) : '') .
                ($group ? (' GROUP BY ' . $group) : '') .
                ($seq ? (' ORDER BY ' . $seq) : '') .
                $limit .
                $ofs . ';',
                $cond[1]
            ) :
            $this->db->exec(
                'SELECT ' . $fields . ' FROM ' . $this->table .
                ($cond ? (' WHERE ' . $cond) : '') .
                ($group ? (' GROUP BY ' . $group) : '') .
                ($seq ? (' ORDER BY ' . $seq) : '') .
                $limit .
                $ofs . ';'
            );
        if ($obj) {
            # Конвертируем массив в объекты Table
            foreach ($rows as &$row) {
                $row = $this->factory($row);
            }
        }

        return $rows;
    }

    /**
     * Обвертка для SELECT запроса; возвращает массив ассоциативных массивов
     * @param string $fields поля
     * @param mixed $cond условия
     * @param string $group группировка
     * @param string $seq сортировка
     * @param int $limit лимит
     * @param int $ofs offset
     * @returns array
     */
    function selectObj($fields = null, $cond = null, $group = null, $seq = null, $limit = 0, $ofs = 0)
    {
        return $this->select($fields, $cond, $group, $seq, $limit, $ofs, true);
    }

    /**
     * Поиск записей, удовлетворяющих условию
     * @param mixed $cond условия
     * @param mixed (string|array) $fields список требуемых столбцов
     * @param string $seq сортировка
     * @param mixed $limit лимит
     * @param int $ofs offset
     * @param boolean $obj конвертировать полученный результат в массив объектов
     * @returns array
     */
    function find($cond = null, $fields = '*', $seq = null, $limit = 0, $ofs = 0, $obj = false)
    {
        $fieldsV = '';
        if ($this->fieldsV) {
            foreach ($this->fieldsV as $field => $val)
                $fieldsV .= ',' . $val[0] . ' AS ' . $field;
        }

        return $this->select((empty($fields) ? ltrim($fieldsV, ',') : $fields . $fieldsV), $cond, null, $seq, $limit, $ofs, $obj);
    }

    /**
     * Поиск записей, удовлетворяющих условию; возвращает массив ассоциативных массивов
     * @param mixed $cond условия
     * @param string $fields список требуемых столбцов
     * @param string $seq сортировка
     * @param mixed $limit лимит
     * @param int $ofs offset
     * @returns object
     */
    function findObj($cond = null, $fields = '*', $seq = null, $limit = 0, $ofs = 0)
    {
        return $this->find($cond, $fields, $seq, $limit, $ofs, true);
    }

    /**
     * Поиск первой записи, удовлетворяющей условию (в виде объекта)
     * @param mixed $cond условия
     * @param string $fields список требуемых столбцов
     * @param string $seq сортировка
     * @param int $ofs offset
     * @returns array
     */
    function findone($cond = null, $fields = '*', $seq = null, $ofs = 0)
    {
        $result = $this->find($cond, $fields, $seq, 1, $ofs);
        list($result) = ($result ? $result : array(null));

        return $result;
    }

    /**
     * Поиск первой записи, удовлетворяющей условию (в виде массива)
     * @param mixed $cond условия
     * @param string $fields список требуемых столбцов
     * @param string $seq сортировка
     * @param int $ofs offset
     * @returns object
     */
    function findoneObj($cond = null, $fields = '*', $seq = null, $ofs = 0)
    {
        $result = $this->findObj($cond, $fields, $seq, 1, $ofs);
        list($result) = ($result ? $result : array(null));

        return $result;
    }

    /**
     * Подсчет кол-во записей, удовлетворяющих условию
     * @param mixed $cond условия
     * @returns integer
     */
    function count($cond = null)
    {
        $this->def('_cnt', 'COUNT(*)');
        list($result) = $this->find($cond, '');
        $this->undef('_cnt');

        return $result['_cnt'];
    }

    /**
     * Очищаем AR
     */
    function reset()
    {
        foreach (array_keys($this->fields) as $field)
            $this->fields[$field] = null;
        if ($this->pkeys)
            foreach (array_keys($this->pkeys) as $pkey)
                $this->pkeys[$pkey] = null;
        if ($this->fieldsV)
            foreach (array_keys($this->fieldsV) as $vfield)
                $this->fieldsV[$vfield][1] = null;
        $this->empty = true;
        $this->mod = null;
        $this->cond = null;
        $this->seq = null;
        $this->ofs = 0;
    }

    /**
     * Наполняем AR данными из первой найденной записи
     * @param mixed $cond условия
     * @param string $fields список требуемых столбцов
     * @param string $seq сортировка
     * @param int $ofs offset
     * @return mixed
     */
    function load($cond = null, $fields = '*', $seq = null, $ofs = 0)
    {
        if ($ofs > -1) {
            $this->ofs = 0;
            if ($obj = $this->findoneObj($cond, $fields, $seq, $ofs)) {
                if (method_exists($this, 'beforeLoad') &&
                    $this->beforeLoad() === false
                ) {
                    return;
                }
                // Наполняем AR
                foreach ($obj->fields as $field => $val) {
                    $this->fields[$field] = $val;
                    if ($this->pkeys &&
                        array_key_exists($field, $this->pkeys)
                    )
                        $this->pkeys[$field] = $val;
                }
                if ($obj->fieldsV) {
                    foreach ($obj->fieldsV as $field => $val)
                        $this->fieldsV[$field][1] = $val[1];
                }
                list($this->empty, $this->cond, $this->seq, $this->ofs) = array(false, $cond, $seq, $ofs);
                if (method_exists($this, 'afterLoad'))
                    $this->afterLoad();

                return $this;
            }
        }
        $this->reset();

        return false;
    }

    /**
     * Наполняем AR данными из n-ной записи, исходя из текущей позиции
     * @param int $ofs offset
     * @return mixed
     */
    function skip($ofs = 1)
    {
        if ($this->isEmpty()) {
            trigger_error(_t('table', 'AR is empty'));

            return false;
        }

        return $this->load($this->cond, $this->seq, $this->ofs + $ofs);
    }

    /**
     * Возвращаем следующую запись
     * @return array
     */
    function next()
    {
        return $this->skip();
    }

    /**
     * Возвращаем предыдущую запись
     * @return array
     */
    function prev()
    {
        return $this->skip(-1);
    }

    /**
     * Сохраняем/создаем запись
     */
    function save()
    {
        if ($this->isEmpty() ||
            method_exists($this, 'beforeSave') &&
            $this->beforeSave() === false
        ) {
            return;
        }

        $new = true;
        if ($this->pkeys) {
            // Если все первичные ключи === NULL => это новая запись
            foreach ($this->pkeys as $pkey)
                if (!is_null($pkey)) {
                    $new = false;
                    break;
                }
        }
        if ($new) {
            // Создаем запись
            $fields = $values = '';
            $bind = array();
            foreach ($this->fields as $field => $val) {
                if (isset($this->mod[$field])) {
                    $fields .= ($fields ? ',' : '') . $field;
                    $values .= ($values ? ',' : '') . ':' . $field;
                    $bind[':' . $field] = array($val, $this->types[$field]);
                }
            }
            if ($bind) {
                $this->db->exec('INSERT INTO ' . $this->table . ' (' . $fields . ')
						         VALUES (' . $values . ');', $bind
                );
            }
            $this->_id = $this->db->pdo->lastInsertId();
        } elseif (!is_null($this->mod)) {
            // Обновляем запись
            $set = $cond = '';
            foreach ($this->fields as $field => $val)
                if (isset($this->mod[$field])) {
                    $set .= ($set ? ',' : '') . $field . '=:' . $field;
                    $bind[':' . $field] = array($val, $this->types[$field]);
                }
            if ($this->pkeys) { // Используем первичные ключи для поиска записи
                foreach ($this->pkeys as $pkey => $val) {
                    $cond .= ($cond ? ' AND ' : '') . $pkey . '=:c_' . $pkey;
                    $bind[':c_' . $pkey] = array($val, $this->types[$pkey]);
                }
            }
            if ($set) {
                $this->db->exec('UPDATE ' . $this->table . ' SET ' . $set . ($cond ? (' WHERE ' . $cond) : '') . ';', $bind);
            }
        }
        if ($this->pkeys) {
            // Обновляем первичные ключи новыми значениями
            foreach (array_keys($this->pkeys) as $pkey)
                $this->pkeys[$pkey] = $this->fields[$pkey];
        }
        $this->empty = false;
        if (method_exists($this, 'afterSave'))
            $this->afterSave();
    }

    /**
     * Удаляем запись(записи)
     * @param mixed $cond условия
     */
    function erase($cond = null)
    {
        if (method_exists($this, 'beforeErase') &&
            $this->beforeErase() === false
        ) {
            return;
        }
        if (!$cond) $cond = $this->cond;
        if ($cond) {
            if (!is_array($cond)) $cond = array($cond, null);
            $this->db->exec('DELETE FROM ' . $this->table . ' WHERE ' . $cond[0], $cond[1]);
        }
        $this->reset();
        if (method_exists($this, 'afterErase'))
            $this->afterErase();
    }

    /**
     * Возвращаем TRUE если AR не наполнен
     * @return bool
     */
    function isEmpty()
    {
        return $this->empty;
    }

    /**
     * Наполняем AR значениями из массива;
     * Виртуальные поля не модифицируются
     * @param array $name массив
     * @param mixed $keys ключи
     */
    function copyFrom($array, $keys = null)
    {
        $keys = (is_null($keys) ? array_keys($array) : preg_split('/[\|;,]/', $keys, 0, PREG_SPLIT_NO_EMPTY));
        foreach ($keys as $key) {
            if (in_array($key, array_keys($array)) &&
                in_array($key, array_keys($this->fields))
            ) {
                if ($this->fields[$key] != $array[$key])
                    $this->mod[$key] = true;
                $this->fields[$key] = $array[$key];
            }
        }
        $this->empty = false;
    }

    /**
     * Наполняем массив значениями из AR
     * @param array $array массив
     * @param string $keys ключи требуемых значений
     */
    function copyTo(&$array, $keys = null)
    {
        if ($this->isEmpty()) {
            trigger_error(_t('table', 'AR is empty'));

            return false;
        }
        $list = array_diff(preg_split('/[\|;,]/', $keys, 0, PREG_SPLIT_NO_EMPTY), array(''));
        $keys = array_keys($this->fields);
        $fieldsV = ($this->fieldsV ? array_keys($this->fieldsV) : null);
        foreach (($fieldsV ? array_merge($keys, $fieldsV) : $keys) as $key) {
            if (empty($list) || in_array($key, $list)) {
                if (in_array($key, array_keys($this->fields)))
                    $array[$key] = $this->fields[$key];
                if ($this->fieldsV &&
                    in_array($key, array_keys($this->fieldsV))
                )
                    $array[$key] = $this->fieldsV[$key];
            }
        }
    }

    /**
     * Синхронизируем AR и SQL структуру таблицы
     * @param string $table таблица
     * @param Database $db объект
     * @param int $ttl TTL
     */
    function sync($table, $db = null, $ttl = 60)
    {
        if (method_exists($this, 'beforeSync') &&
            $this->beforeSync() === false
        ) {
            return;
        }
        // Инициализируем AR
        list($this->db, $this->table) = array($db, $table);
        if ($schema = $db->schema($table, $ttl)) {
            // Заполняем свойства
            foreach ($schema['result'] as $row) {
                $this->fields[$row[$schema['field']]] = null;
                if ($row[$schema['pkname']] == $schema['pkval']) {
                    // Сохраняем первичные ключи
                    $this->pkeys[$row[$schema['field']]] = null;
                }
                $this->types[$row[$schema['field']]] =
                    (preg_match('/int|bool/i', $row[$schema['type']], $match) ?
                        constant('PDO::PARAM_' . strtoupper($match[0])) : \PDO::PARAM_STR);
            }
            $this->empty = true;
        }
        if (method_exists($this, 'afterSync'))
            $this->afterSync();
    }

    /**
     * Создаем виртуальное поле
     * @param string $field ключ
     * @param string $expr выражение
     */
    function def($field, $expr)
    {
        if (array_key_exists($field, $this->fields)) {
            trigger_error(_t('table', 'Name conflict with AR-mapped field'));

            return;
        }
        $this->fieldsV[$field] = array($expr, null);
    }

    /**
     * Удаляем виртуальное поле
     * @param string $field ключ
     */
    function undef($field)
    {
        if (array_key_exists($field, $this->fields) || !$this->isdef($field)) {
            trigger_error(_t('table', 'Cannot undefine an AR-mapped field [field]', array('field' => $field)));

            return;
        }
        unset($this->fieldsV[$field]);
    }

    /**
     * Возвращаем TRUE если виртуальное поле существует
     * @param string $field ключ
     */
    function isdef($field)
    {
        return ($this->fieldsV && array_key_exists($field, $this->fieldsV));
    }

    /**
     * Возвращаем значение по ключу
     * @param string $field ключ
     * @return mixed
     */
    function &__get($field)
    {
        if (array_key_exists($field, $this->fields))
            return $this->fields[$field];
        if ($this->isdef($field))
            return $this->fieldsV[$field][1];

        return false;
    }

    /**
     * Закрепляем значение по ключу
     * @param string $field ключ
     * @param mixed $val значение
     * @return bool
     */
    function __set($field, $val)
    {
        if (array_key_exists($field, $this->fields)) {
            if ($this->fields[$field] != $val && !isset($this->mod[$field]))
                $this->mod[$field] = true;
            $this->fields[$field] = $val;
            if (!is_null($val))
                $this->empty = false;

            return true;
        }
        if ($this->isdef($field)) {
            trigger_error(_t('table', 'Virtual fields are read-only'));
        }

        return false;
    }

    /**
     * Генерируем ошибку в момент unset'a переменной
     * @param string $field ключ
     */
    function __unset($field)
    {
        trigger_error(_t('table', 'Cannot unset an AR-mapped field [field]', array('field' => $field)));
    }

    /**
     * Возвращаем TRUE в случае существования поля
     * @param $field string
     * @return bool
     */
    function __isset($field)
    {
        return (array_key_exists($field, $this->fields) ||
            $this->fieldsV && array_key_exists($field, $this->fieldsV));
    }

    /**
     * Конструктор
     * @return Table
     */
    function __construct()
    {
        // Синхронизируем
        $args = func_get_args();
        call_user_func_array(array($this, 'sync'), $args);
    }

}