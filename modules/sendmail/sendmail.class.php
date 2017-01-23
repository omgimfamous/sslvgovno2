<?php

class Sendmail extends SendmailBase
{
    /**
     * Cron: Массовая рассылка писем
     * - рекомендуемая периодичность: каждые 2-5 минут
     */
    public function cronMassend()
    {
        if (!bff::cron()) {
            return;
        }

        $this->db->begin(); # для LOCK записей таблицы получателей рассылки

        # получаем информацию об открытой рассылке
        $aMassendData = $this->db->one_array('SELECT * FROM ' . TABLE_MASSEND . ' WHERE status = 0 LIMIT 1');
        if (empty($aMassendData)) {
            return;
        }

        $aSettings = func::unserialize($aMassendData['settings']);
        if ($aSettings === false || empty($aSettings)) {
            $this->errors->set('corrupted massend-settings data (id=' . $aMassendData['id'] . ')');

            return;
        }

        # формируем текст письма на основе шаблона "massend"
        $isHTML = (isset($aSettings['is_html']) ? !empty($aSettings['is_html']) : NULL);
        $wrapperID = (isset($aSettings['wrapper_id']) ? !empty($aSettings['wrapper_id']) : 0);
        $aTemplate = $this->getMailTemplate('sendmail_massend', array('msg' => $aSettings['body']), LNG, $isHTML, $wrapperID);
        $aSettings['body'] = $aTemplate['body'];

        // SELECT ... FOR UPDATE (mysql)
        $aUsersID = $this->db->select_one_column('SELECT user_id FROM ' . TABLE_MASSEND_RECEIVERS . '
                            WHERE massend_id = ' . $aMassendData['id'] . ' AND success = 0
                            LIMIT 100
                            FOR UPDATE'
        );
        if (empty($aUsersID)) {
            $this->errors->set('massend: no receivers to send');

            return;
        }

        $aReceivers = $this->db->select('SELECT user_id as id, name, surname, email
                                FROM ' . TABLE_USERS . '
                            WHERE user_id IN (' . implode(',', $aUsersID) . ')
                            ORDER BY user_id'
        );

        $mailer = new CMail();

        $mailer->From = $aSettings['from'];
        $mailer->Subject = $aSettings['subject'];
        $mailer->addCustomHeader('Precedence', 'bulk'); # индикатор массовой рассылки для Google

        if (BFF_DEBUG)
            bff::log('massend started: ' . sizeof($aReceivers) . ' receivers', 'cron.log');

        $aResult = array();
        $nSuccess = 0;
        foreach ($aReceivers as $v) {
            $mailer->AddAddress($v['email']);

            $mailer->AltBody = '';
            $mailer->MsgHTML(strtr($aSettings['body'], array('{fio}' => $v['name'])));

            $res = $mailer->Send() ? 1 : 0;
            if ($res) {
                $nSuccess++;
                $aResult[] = $v['id'];
            }

            $mailer->ClearAddresses();

            usleep(100000); // sleep for 0.1 second
        }

        if (!empty($aResult)) {
            $this->db->update(TABLE_MASSEND_RECEIVERS, array(
                    'success' => 1,
                ), array(
                    'massend_id' => $aMassendData['id'],
                    'user_id'    => $aResult,
                )
            );

            $aUpdate = array('success = success + ' . $nSuccess);
            # закрываем рассылку - если кол-во получателей == кол-во отправленных писем
            if ($aMassendData['total'] == ($aMassendData['success'] + $nSuccess)) {
                $aUpdate['status'] = 1;
                $aUpdate['finished'] = $this->db->now();
            }

            $this->db->update(TABLE_MASSEND, $aUpdate, array('id'=>$aMassendData['id']));

            if (BFF_DEBUG)
                bff::log('massend finish: ' . $nSuccess, 'cron.log');
        }

        $this->db->commit();
    }
}