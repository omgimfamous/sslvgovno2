<?php

# Работа с почтой - массовая рассылка (sendmail)
define('TABLE_MASSEND', DB_PREFIX . 'massend');
define('TABLE_MASSEND_RECEIVERS', DB_PREFIX . 'massend_receivers');

class SendmailModel extends SendmailModelBase
{

    /**
     * Формируем задание на рассылку и заполняем список получателей
     * @param array $aSettings - данные для рассылки
     * @return bool
     */
    public function massendStart($aSettings)
    {
        if(empty($aSettings)) return false;
        $nReceiversTotal = $this->db->one_data('
            SELECT COUNT(*) FROM ' . TABLE_USERS . '
            WHERE (enotify & ' . Users::ENOTIFY_NEWS . ') AND enotify > 0 AND blocked = 0 AND activated = 1
        ');
        $nMassendID = $this->db->insert(TABLE_MASSEND, array(
                'total'    => $nReceiversTotal,
                'started'  => $this->db->now(),
                'settings' => serialize($aSettings),
            )
        );
        if (empty($nMassendID)) {
            return false;
        }
        # сохраняем ID получателей(пользователей) в базу
        $this->db->exec('
            INSERT INTO '.TABLE_MASSEND_RECEIVERS.'(massend_id, user_id)
            SELECT :massend_id, user_id FROM ' . TABLE_USERS . '
            WHERE (enotify & ' . Users::ENOTIFY_NEWS . ') AND enotify > 0 AND blocked = 0 AND activated = 1
            ORDER BY user_id ASC ', array(':massend_id' => $nMassendID));
        return true;
    }
}