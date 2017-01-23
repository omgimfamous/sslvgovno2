<?php

/**
 * Используемые таблицы:
 * TABLE_BILLS - таблица счетов
 * TABLE_USERS - таблица пользователей
 */

class BillsModelBase extends Model
{
    /** @var array список шифруемых полей в таблице TABLE_BILLS */
    public $cryptBills = array();

    /**
     * Список счетов по фильтру (adm)
     * @param array $aFilterRaw фильтр списка (требует подготовки)
     * @param bool $bCount только подсчет кол-ва
     * @param string $sqlLimit
     * @param string $sqlOrderBy
     * @return mixed
     */
    function billsListing(array $aFilterRaw, $bCount = false, $sqlLimit = '', $sqlOrderBy = '')
    {
        $aFilter = array();
        $aBind = array();
        $f = $aFilterRaw;
        if ($f['id'] > 0) {
            $aFilter['id'] = $f['id'];
        }
        if (!empty($f['item']) && $f['item'] > 0) {
            $aFilter['item_id'] = $f['item'];
        }
        if ($f['uid'] > 0) {
            $aFilter['user_id'] = $f['uid'];
        }
        if ($f['status'] > 0) {
            $aFilter['status'] = $f['status'];
        }
        if ($f['type'] > 0) {
            $aFilter['type'] = $f['type'];
        }
        if ($f['svc'] > 0) {
            $aFilter['svc_id'] = $f['svc'];
        }

        if (!empty($f['p_from']) || !empty($f['p_to'])) {
            $from = strtotime($f['p_from']);
            $to = strtotime($f['p_to']);

            if (!empty($from) && $from != -1) {
                $aFilter[] = 'created >= :createdFrom';
                $aBind[':createdFrom'] = date('Y-m-d 00:00:00', $from);
            }
            if (!empty($to) && $to != -1) {
                $aFilter[] = 'created <= :createdTo';
                $aBind[':createdTo'] = date('Y-m-d 23:59:59', $to);
            }
        }

        $aFilter = $this->prepareFilter($aFilter, 'B', $aBind);

        if ($bCount) {
            return (integer)$this->db->one_data('SELECT COUNT(B.id)
                                FROM ' . TABLE_BILLS . ' B
                                ' . $aFilter['where'],
                $aFilter['bind']
            );
        }

        return $this->db->select('SELECT B.*, U.name, ' . (Users::model()->userEmailCrypted() ? 'BFF_DECRYPT(U.email) as email' : 'U.email') . '
                          FROM ' . TABLE_BILLS . ' B
                            LEFT JOIN ' . TABLE_USERS . ' U ON B.user_id = U.user_id
                          ' . $aFilter['where'] .
            (!empty($sqlOrderBy) ? ' ORDER BY B.' . $sqlOrderBy : '') .
            (!empty($sqlLimit) ? ' ' . $sqlLimit : ''),
            $aFilter['bind']
        );

    }

    /**
     * Список счетов по фильтру (frontend)
     * @param array $aFilter фильтр списка
     * @param bool $bCount только подсчет кол-ва
     * @param string $sqlLimit
     * @param string $sqlOrder
     * @return mixed
     */
    function billsList(array $aFilter, $bCount, $sqlLimit = '', $sqlOrder = 'created DESC')
    {
        $aFilter = $this->prepareFilter($aFilter, 'B');
        if ($bCount) {
            return (integer)$this->db->one_data('SELECT COUNT(B.id)
                                FROM ' . TABLE_BILLS . ' B
                                ' . $aFilter['where'],
                $aFilter['bind']
            );
        }

        return $this->db->select('SELECT B.*
                          FROM ' . TABLE_BILLS . ' B
                          ' . $aFilter['where']
            . (!empty($sqlOrder) ? ' ORDER BY B.' . $sqlOrder : '')
            . $sqlLimit,
            $aFilter['bind']
        );
    }

    /**
     * Создаем/обновляем счет
     * @param int $nBillID ID счета
     * @param array $aData данные
     * @return bool|int
     */
    function billSave($nBillID, array $aData)
    {
        if (empty($aData)) {
            return false;
        }
        if (isset($aData['svc_settings'])) {
            if (!is_array($aData['svc_settings'])) {
                $aData['svc_settings'] = array();
            }
            $aData['svc_settings'] = serialize($aData['svc_settings']);
        }

        if ($nBillID > 0) {
            $res = $this->db->update(TABLE_BILLS, $aData, array('id' => $nBillID), array(), $this->cryptBills);

            return !empty($res);
        } else {
            $aData['created'] = $this->db->now();
            $aData['ip'] = Request::remoteAddress();

            return $this->db->insert(TABLE_BILLS, $aData, 'id', array(), $this->cryptBills);
        }
    }

    /**
     * Получаем данные о счете
     * @param int|array $nBillID ID счета или параметры поиска счета
     * @param array $aFields список требуемых полей данных
     * @param int|bool $nUserID ID пользователя (проверяем на принадлежность к пользователю)
     * @return mixed
     */
    function billData($nBillID, $aFields = array('*'), $nUserID = false)
    {
        if (is_array($nBillID)) {
            $aFilter = $nBillID;
            if (!empty($nUserID) && !isset($aFilter['user_id'])) {
                $aFilter['user_id'] = $nUserID;
            }
        } else {
            $aFilter = array('id'=>$nBillID);
            if (!empty($nUserID)) {
                $aFilter['user_id'] = $nUserID;
            }
        }

        if (empty($aFields)) {
            $aFields = array('*');
        }
        if (!is_array($aFields)) {
            $aFields = array($aFields);
        }
        if (!empty($this->cryptBills)) {
            foreach ($aFields as $k => $v) {
                if (in_array($v, $this->cryptBills)) {
                    $aFields[$k] = "BFF_DECRYPT($v) as $v";
                }
            }
        }
        $aFields = join(',', $aFields);

        $aFilter = $this->prepareFilter($aFilter);
        $aData = $this->db->one_array('SELECT ' . $aFields . '
                FROM ' . TABLE_BILLS . '
                '.$aFilter['where'], $aFilter['bind']
        );

        if (isset($aData['svc_settings'])) {
            $aData['svc_settings'] = func::unserialize($aData['svc_settings']);
        }

        return $aData;
    }
}