<?php

/**
 * Права доступа группы:
 *  - sendmail: Работа с почтой
 *      - massend: Массовая рассылка
 */
class Sendmail extends SendmailBase
{
    //---------------------------------------------------------------
    // рассылка писем

    public function massend_form()
    {
        if (!$this->haveAccessTo('massend')) {
            return $this->showAccessDenied();
        }

        $aData = array();
        $aData['noreply'] = config::sys('mail.noreply');
        $aData['wrappers'] = $this->model->wrappersOptions(0, '- Без шаблона -');
        return $this->viewPHP($aData, 'admin.massend');
    }

    public function massend_listing()
    {
        if (!$this->haveAccessTo('massend')) {
            return $this->showAccessDenied();
        }

        $aData['items'] = $this->db->select('SELECT * FROM ' . TABLE_MASSEND . ' ORDER BY id DESC');

        return $this->viewPHP($aData, 'admin.massend.listing');
    }

    public function ajax()
    {
        if (!$this->haveAccessTo('massend')) {
            return $this->showAccessDenied();
        }

        $aResponse = array();

        switch ($this->input->getpost('act', TYPE_STR)) {
            case 'massend-init': # инициализация рассылки
            {

                $this->input->postm(array(
                        'test'    => TYPE_BOOL,
                        'from'    => TYPE_STR,
                        'subject' => TYPE_STR,
                        'body'    => TYPE_STR,
                        'is_html' => TYPE_BOOL,
                        'wrapper_id' => TYPE_BOOL,
                    ), $p
                );
                extract($p, EXTR_REFS);

                if (!$is_html) {
                    $body = nl2br($body);
                }
                $body = preg_replace("@<script[^>]*?>.*?</script>@si", '', $body);

                // set_time_limit(0);
                ignore_user_abort(true);

                if (!(!empty($from) && !empty($subject) && !empty($body))) {
                    $this->errors->impossible();
                    break;
                }

                if ($test) {
                    $aReceiversTest = $this->input->post('receivers_test', TYPE_STR);
                    $aReceiversTest = explode(',', $aReceiversTest);
                    if (!empty($aReceiversTest)) {
                        $aReceivers = array_map('trim', $aReceiversTest);
                    }

                    $nSendSuccess = 0;
                    $time_start = microtime(true);
                    $nReceiversTotal = sizeof($aReceivers);
                    $aReceiversSended = array();

                    # формируем текст письма на основе шаблона "massend"
                    $aTemplate = $this->getMailTemplate('sendmail_massend', array('msg' => $body), LNG, $is_html);
                    $body = $aTemplate['body'];

                    $mailer = new CMail();
                    $mailer->From = $from;
                    $mailer->Subject = $subject;
                    $mailer->MsgHTML($body);

                    for ($i = 0; $i < $nReceiversTotal; $i++) {
                        $mailer->AddAddress($aReceivers[$i]);
                        if ($mailer->Send()) {
                            $nSendSuccess++;
                        }

                        $mailer->ClearAddresses();
                        usleep(150000); // sleep for 0.15 second
                    }

                    $time_total = (microtime(true) - $time_start); //останавливаем секундомер

                    $this->ajaxResponse(array(
                            'total'      => $nReceiversTotal,
                            'success'    => $nSendSuccess,
                            'failed'     => ($nReceiversTotal - $nSendSuccess),
                            'sended'     => $aReceiversSended,
                            'time_total' => sprintf('%0.2f', $time_total),
                            'time_avg'   => sprintf('%0.2f', (!empty($nReceiversTotal) ? ($time_total / $nReceiversTotal) : 0)),
                            'res'        => $this->errors->no()
                        )
                    );
                } else {
                    # формируем список получателей, исключая заблокированных/неактивированных/не подписавшихся на рассылку
                    if( ! $this->model->massendStart(array(
                            'from'       => $from,
                            'subject'    => $subject,
                            'body'       => $body,
                            'is_html'    => $is_html,
                            'wrapper_id' => $wrapper_id,
                            'time_total' => 0,
                            'time_avg'   => 0
                        ))){
                        $this->errors->set('Ошибка инициализации рассылки');
                        break;
                    }
                }

            }
            break;
            case 'massend-delete': # удаление рассылки
            {

                $nMassendID = $this->input->post('rec', TYPE_UINT);
                if (!$nMassendID) {
                    $this->errors->impossible();
                    break;
                }

                $this->db->delete(TABLE_MASSEND, $nMassendID);
                $this->db->delete(TABLE_MASSEND_RECEIVERS, array('massend_id' => $nMassendID));
            }
            break;
            case 'massend-info': # получение сведений о рассылке
            {
                $nMassendID = $this->input->get('id', TYPE_UINT);
                if (!$nMassendID) {
                    $this->errors->unknownRecord();
                    break;
                }

                $aData = $this->db->one_array('SELECT * FROM ' . TABLE_MASSEND . ' WHERE id = :id', array(':id' => $nMassendID));
                if (empty($aData)) {
                    $this->errors->unknownRecord();
                    break;
                }

                $aSettings = func::unserialize($aData['settings']);
                foreach (array('from','subject','body','time_total','time_avg') as $k) {
                    if (isset($aSettings[$k])) $aData[$k] = $aSettings[$k];
                }

                echo $this->viewPHP($aData, 'admin.massend.info');
                exit;
            }
            break;
            default:
            {
                $this->errors->impossible();
            }
        }

        $this->ajaxResponseForm($aResponse);
    }

}