<?php

class UsersModel extends UsersModelBase
{
    public function init()
    {
        parent::init();

        # список полей данных о пользователе запрашиваемых для сохранения в сессии
        $this->userSessionDataKeys = array(
            'user_id as id',
            'member',
            'login',
            'shop_id',
            'password',
            'password_salt',
            'email',
            'phone_number',
            'phone_number_verified',
            'name',
            'surname',
            'phone',
            'avatar',
            'sex',
            'birthdate',
            'activated',
            'blocked',
            'blocked_reason',
            'admin',
            'last_login',
            'region_id',
            'social_id',
        );
        # список полей счетчиков
        $this->userStatCounters = array('internalmail_new', 'items', 'items_fav',);
    }

    /**
     * Получаем данные о пользователе по ID
     * @param int $nUserID ID пользователя
     * @param mixed $aDataKeys ключи необходимых данных
     * @param bool $bEdit true - выполнить подготовку данных для редактирования
     * @return array|mixed
     */
    public function userData($nUserID, $aDataKeys = array(), $bEdit = false)
    {
        $aData = $this->userDataByFilter(array('user_id' => $nUserID), $aDataKeys);
        if ($bEdit) {
            if (isset($aData['region_id'])) {
                $aData['region_title'] = Geo::regionTitle($aData['region_id']);
            }
        }
        if (isset($aData['phones'])) {
            $aData['phones'] = (!empty($aData['phones']) ? func::unserialize($aData['phones']) : array());
        }

        return $aData;
    }

    /**
     * Данные о пользователе для правого блока
     * @param integer $nUserID ID пользователя
     * @param array $aFieldsEx ключи доп. полей
     * @return array
     */
    public function userDataSidebar($nUserID, $aFieldsEx = array())
    {
        $aFields = array('user_id as id', 'name', 'login', 'shop_id', 'created', 'avatar', 'sex',
                         'phones', 'phone_number', 'phone_number_verified', 'skype', 'icq');
        if (!empty($aFieldsEx)) {
            $aFields = array_merge($aFields, $aFieldsEx);
        }
        $aData = $this->userData($nUserID, $aFields);
        if (!empty($aData)) {
            $aData['link'] = Users::urlProfile($aData['login']);
            $aData['avatar'] = UsersAvatar::url($nUserID, $aData['avatar'], UsersAvatar::szNormal, $aData['sex']);
            if (Users::registerPhoneContacts() && $aData['phone_number'] && $aData['phone_number_verified']) {
                array_unshift($aData['phones'], array('v'=>$aData['phone_number'],'m'=>mb_substr($aData['phone_number'], 0, 2) . 'x xxx xxxx'));
            }
        }

        return $aData;
    }

    /**
     * Получаем данные о пользователе - для отправки ему email-уведомления.
     * Проверяем возможно ли отправить письмо пользователю и если нет возвращаем FALSE.
     * @param integer $nUserID ID пользователя
     * @param integer $nEnotifyID ID настройки email-уведомления, 0 - не выполнять проверку настроек уведомлений
     * @param array $aDataKeys требуемые данные
     * @return array|boolean данные или FALSE
     */
    public function userDataEnotify($nUserID, $nEnotifyID = 0, array $aDataKeys = array())
    {
        $aDataKeys = array_merge($aDataKeys, array(
                'user_id',
                'name',
                'email',
                'login',
                'blocked',
                'enotify',
                'activated'
            )
        );
        $aData = $this->userData($nUserID, $aDataKeys, false);

        do {
            # не нашли такого пользователя
            if (empty($aData)) {
                break;
            }
            # заблокирован / неактивирован
            if ($aData['blocked'] || !$aData['activated']) {
                break;
            }
            # запретил email-уведомления
            if ($nEnotifyID > 0) {
                if (empty($aData['enotify']) || !($aData['enotify'] & $nEnotifyID)) {
                    break;
                }
            }
            # email адрес некорректный
            if (!$this->input->isEmail($aData['email'])) {
                break;
            }

            return $aData;
        } while (false);

        return false;
    }

    /**
     * Удаляем неактивированные аккаунты с просроченным периодом активации
     * Период действия ссылки активации аккаунта пользователя - 7 дней (Users::getActivationInfo)
     * Период действия ссылки активации объявления - 1 день (BBS::getActivationInfo)
     */
    public function usersCronDeleteNotActivated()
    {
        //$this->deleteUnactivated(true);
        return;
    }

    /**
     * Удаление неактивированных пользователей
     * @param boolean $bAll удалить всех неактивных
     * @param array $aUsersID ID удаляемых пользователей
     * @return array ID успешно удаленных пользователей
     */
    public function deleteUnactivated($bAll, array $aUsersID = array())
    {
        $aFilter = array(
            'activated' => 0,
            ':sa'       => 'U.user_id != 1',
            ':author'   => 'IM.author IS NULL',
            ':items'    => 'I.id IS NULL',
        );

        if (!$bAll) {
            if (empty($aUsersID)) {
                return array();
            }
            $aFilter['user_id'] = $aUsersID;
        }

        $aFilter = $this->prepareFilter($aFilter, 'U');

        # Инициализируем объект модуля InternalMail для работы с таблицей TABLE_INTERNALMAIL
        InternalMail::i();

        # Удаляем пользователей без закрепленных за ними:
        # - объявлений
        # - сообщений во внутренней почте
        $aUsersID = $this->db->select_one_column('
            SELECT U.user_id
            FROM '.TABLE_USERS.' U
                LEFT JOIN '.TABLE_INTERNALMAIL.' IM ON IM.author = U.user_id
                LEFT JOIN '.TABLE_BBS_ITEMS.' I ON I.user_id = U.user_id
            '.$aFilter['where'], $aFilter['bind']);
        if (!empty($aUsersID)) {
            $this->db->delete(TABLE_USERS, array('user_id' => $aUsersID));
            $this->db->delete(TABLE_USERS_STAT, array('user_id' => $aUsersID));
            $this->db->delete(TABLE_USER_IN_GROUPS, array('user_id' => $aUsersID));
        }

        return $aUsersID;
    }

}