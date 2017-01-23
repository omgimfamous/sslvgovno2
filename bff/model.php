<?php

/**
 * Базовый класс модели
 * @abstract
 * @version 0.29
 * @modified 24.feb.2014
 */

use bff\db\Table;

abstract class Model extends Component
{
    /** @var Module - ссылка на контроллер(модуль) */
    protected $controller;

    public function __construct($controller)
    {
        parent::init();

        $this->controller = $controller;
    }

    /**
     * Объект ActiveRecord
     * @param string $table название таблицы
     * @return Table
     */
    public function ar($table)
    {
        static $_ar = array();
        if (empty($_ar[$table])) {
            $_ar[$table] = new Table($table, $this->db);
        }

        return $_ar[$table];
    }

    public function getList($sTable, $aCond, $aFields = '*', $sOrder = '', $mLimit = 0, $nOffset = 0)
    {
        if (is_array($aFields)) {
            $aFields = join(', ', $aFields);
        }

        return $this->ar($sTable)->find($aCond, $aFields, $sOrder, $mLimit, $nOffset);
    }

    public function getListCount($sTable, $aCond)
    {
        return $this->ar($sTable)->count($aCond);
    }

    /**
     * Формируем SQL-фильтр @see static::filter
     * @param array $aFilter параметры
     * @param string|boolean $sPrefix префикс
     * @param array $aBind данные для биндинга
     * @return array
     */
    public function prepareFilter(array $aFilter = array(), $sPrefix = '', array $aBind = array())
    {
        return static::filter($aFilter, $sPrefix, $aBind);
    }

    /**
     * Формируем SQL-фильтр запроса
     * @param array $aFilter параметры, @examples:
     *  $aFilter['status'] = 7; (prefix+)
     *  $aFilter[':status'] = '(status IN (1,2,3))'; (as is)
     *  $aFilter[':status'] = array('(status >= :min OR status <= :max)', ':min'=>1, ':max'=>3); (as is + bind)
     *  $aFilter[] = 'status IS NOT NULL'; (prefix+)
     *  $aFilter[] = array('title LIKE :title', ':title'=>'Super Title'); (as is + bind)
     * @param string|boolean $sPrefix префикс
     * @param array $aBind данные для биндинга
     * @return array ('where'=>string,'bind'=>array|NULL)
     */
    public static function filter(array $aFilter = array(), $sPrefix = '', array $aBind = array())
    {
        $sPrefix = (!empty($sPrefix) ? $sPrefix . '.' : '');
        $sqlWhere = '';
        if (!empty($aFilter)) {
            if (is_array($aFilter)) {
                $sqlWhere = array();
                foreach ($aFilter as $key => $val) {
                    if (is_int($key)) {
                        if (is_string($val)) {
                            ## filter[] = 'status IS NOT NULL';
                            $sqlWhere[] = $sPrefix . $val;
                        } else {
                            if (is_array($val) && sizeof($val) >= 2) { // condition + binds
                                ## filter[] = array('num > :x', ':x'=>9)
                                $sqlWhere[] = array_shift($val);
                                foreach ($val as $k => $v) {
                                    $aBind[$k] = $v;
                                }
                            }
                        }
                    } else {
                        if (is_string($key)) {
                            if ($key{0} == ':') {
                                if (is_string($val)) { // condition
                                    ## filter[:range] = '(total > 0 OR total < 10)'
                                    $sqlWhere[] = $val;
                                } elseif (is_array($val) && sizeof($val) >= 2) { // one condition + binds
                                    ## filter[:num] = array('num > :x', ':x'=>9)
                                    $sqlWhere[] = array_shift($val);
                                    foreach ($val as $k => $v) {
                                        $aBind[$k] = $v;
                                    }
                                }
                            } else {
                                if (is_array($val)) {
                                    ## filter['id'] = array(1,2,5);
                                    $sqlWhere[] = (!empty($val) ? $sPrefix : '') . bff::database()->prepareIN($key, $val); // IN - only integers
                                } else {
                                    ## filter['status'] = 7;
                                    $sqlWhere[] = $sPrefix . $key . ' = :' . $key;
                                    $aBind[':' . $key] = $val;
                                }
                            }
                        }
                    }
                }
                $sqlWhere = 'WHERE ' . join(' AND ', $sqlWhere);
            } elseif (is_string($aFilter)) {
                $sqlWhere = 'WHERE ' . $sPrefix . $aFilter;
            }
        }

        return array('where' => " $sqlWhere ", 'bind' => (!empty($aBind) ? $aBind : null));
    }

    /**
     * Инвертирование поля типа "enabled"
     * @param string $table таблица
     * @param integer $recordID ID записи
     * @param string $fieldToggle название поля "enabled"
     * @param string $fieldID название поля "id"
     * @param bool $withRotation учитывать ротацию по полю "enabled"
     * @return mixed
     */
    public function toggleInt($table, $recordID, $fieldToggle = 'enabled', $fieldID = 'id', $withRotation = false)
    {
        if ($withRotation) {
            $aData = $this->db->one_array("SELECT $fieldToggle FROM $table WHERE $fieldID = :id", array(':id' => $recordID));
            if (empty($aData[$fieldToggle])) {
                $nMax = (int)$this->db->one_data("SELECT MAX($fieldToggle) FROM $table");

                return $this->db->update($table, array($fieldToggle => $nMax + 1), "$fieldID = :id", array(':id' => $recordID));
            } else {
                return $this->db->exec("UPDATE $table SET $fieldToggle = 0 WHERE $fieldID = :id", array(':id' => $recordID));
            }
        } else {
            return $this->db->exec("UPDATE $table SET $fieldToggle = (1 - $fieldToggle) WHERE $fieldID = :id", array(':id' => $recordID));
        }
    }

}