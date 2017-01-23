<?php

/**
 * Права доступа группы:
 *  - bills: Счета
 *      - listing: Просмотр списка счетов
 *      - manage: Управление счетами
 */

abstract class BillsModule extends BillsModuleBase
{
    function listing()
    {
        if (!$this->haveAccessTo('listing')) {
            return $this->showAccessDenied();
        }

        switch ($this->input->getpost('act', TYPE_STR)) {
            case 'ubalance-gift':
            {
                if (!$this->haveAccessTo('manage')) {
                    return $this->showAccessDenied();
                }

                $p = $this->input->postm(array(
                        'uid'         => TYPE_UINT,
                        'amount'      => TYPE_UNUM,
                        'description' => TYPE_STR,
                    )
                );
                extract($p, EXTR_REFS);

                if (!$this->security->isSuperAdmin()) {
                    return $this->showAccessDenied();
                }

                if (!$uid || $amount <= 0 || empty($description)) {
                    return $this->showImpossible();
                }

                $nBalance = bff::model('users')->userBalance($uid);
                if ($nBalance === false) {
                    return $this->showImpossible();
                }

                $res = $this->updateUserBalance($uid, $amount, true);
                if (empty($res)) {
                    return $this->showImpossible();
                }

                // создаем завершенный счет пополнения - подарок
                $nBillID = $this->createBill_InGift($uid, $nBalance + $amount, $amount, $description);

                $this->adminRedirect((!empty($nBillID) ? Errors::SUCCESS : Errors::IMPOSSIBLE), 'listing&uid=' . $uid);

            }
            break;
        }

        $f = $this->input->postgetm(array(
                'id'     => TYPE_UINT,
                'item'   => TYPE_UINT,
                'uid'    => TYPE_UINT,
                'status' => TYPE_UINT,
                'type'   => TYPE_UINT,
                'svc'    => TYPE_UINT,
                'offset' => TYPE_UINT,
                'p_from' => TYPE_NOTAGS,
                'p_to'   => TYPE_NOTAGS,
            )
        );

        $nLimit = 20;
        $aData = array();
        $aData['f'] = & $f;
        $aData['orders'] = array('id' => 'desc', 'created' => 'desc');
        if ($f['offset'] <= 0) {
            $f['offset'] = 0;
        }
        $aData['offset'] = $f['offset'];

        $f += $this->prepareOrder($orderBy, $orderDirection, 'id' . tpl::ORDER_SEPARATOR . 'desc', $aData['orders']);

        $f['order'] = $orderBy . tpl::ORDER_SEPARATOR . $orderDirection;

        $aData['bills'] = $this->model->billsListing($f, false,
            $this->db->prepareLimit($f['offset'], $nLimit + 1),
            "$orderBy $orderDirection"
        );

        $this->generatePagenationPrevNext(null, $aData, 'bills', $nLimit, 'jBills');

        $aData['list'] = $this->viewPHP($aData, 'admin.listing.ajax', $this->module_dir_tpl_core);

        if (Request::isAJAX()) {
            $this->ajaxResponse(array(
                'list'   => $aData['list'],
                'pgn'    => $aData['pgn'],
                'filter' => $f,
            ));
        }

        $aData['access_edit'] = $this->haveAccessTo('manage');
        if ($f['uid'] > 0) {
            $aData['user'] = Users::model()->userDataByFilter($f['uid'], array('user_id as id', 'email', 'balance'));
        }
        $aData['curr'] = Site::currencyDefault(false);
        $aData['status_data'] = $this->getStatusData();
        $aData['type_options'] = $this->getTypeOptions($f['type']);
        $aData['svc_options'] = Svc::model()->svcOptions($f['svc']);
        tpl::includeJS(array('autocomplete', 'datepicker'), true);

        return $this->viewPHP($aData, 'admin.listing', $this->module_dir_tpl_core);
    }

    function ajax()
    {
        if (!Request::isAJAX()) {
            $this->ajaxResponse(Errors::ACCESSDENIED);
        }

        $nBillID = $this->input->post('bid', TYPE_UINT);

        switch ($this->input->get('act', TYPE_STR)) {
            case 'user-autocomplete': #autocomplete
            {
                $sQ = $this->input->post('q', TYPE_STR);
                # получаем список подходящих по email'у пользователей, исключая:
                # - неактивированных пользователей
                if (Users::model()->userEmailCrypted()) {
                    $aResult = $this->db->select('SELECT U.user_id as id, BFF_DECRYPT(U.email) as email FROM ' . TABLE_USERS . ' U
                                  WHERE U.activated = 1
                                    AND BFF_DECRYPT(U.email) LIKE (:q)
                                  ORDER BY 2
                                  LIMIT 10',
                        array(':q' => $sQ . '%')
                    );
                } else {
                    $aResult = $this->db->select('SELECT U.user_id as id, U.email FROM ' . TABLE_USERS . ' U
                                  WHERE U.activated = 1
                                    AND U.email LIKE (:q)
                                  ORDER BY 2
                                  LIMIT 10',
                        array(':q' => $sQ . '%')
                    );
                }
                $this->autocompleteResponse($aResult, 'id', 'email');
            }
            break;
            /**
             * Изменение статуса счета:
             * @param post ::bid $nBillID ID счета
             * @param post ::integer $nStatus ID статуса, допустимые: COMPLETED, CANCELED
             */
            case 'status':
            {
                if (!$this->haveAccessTo('manage')) {
                    $this->ajaxResponse(Errors::ACCESSDENIED);
                }

                if (!$nBillID) {
                    $this->ajaxResponse(Errors::IMPOSSIBLE);
                }

                $nStatus = $this->input->post('status', TYPE_UINT);
                if (!in_array($nStatus, array(self::STATUS_COMPLETED, self::STATUS_CANCELED))) {
                    $this->ajaxResponse(Errors::IMPOSSIBLE);
                }

                $aBill = $this->model->billData($nBillID, array('user_id', 'type', 'status', 'amount', 'svc_id'));
                if (!$aBill) {
                    $this->ajaxResponse(Errors::IMPOSSIBLE);
                }

                if ($nStatus === $aBill['status']) {
                    # требуемый статус = текущему
                    $this->ajaxResponse(Errors::IMPOSSIBLE);
                }

                # закрываем счет
                if ($nStatus == self::STATUS_COMPLETED) {
                    $bInPay = ($aBill['type'] == self::TYPE_IN_PAY && $aBill['user_id'] > 0);
                    $mUserBalance = false;
                    if (!$bInPay || !empty($aBill['svc_id'])) {
                        # закрыть можно только счет типа "пополнение счета" (TYPE_IN_PAY)
                        # TODO: добавить возможность закрывать все типы счетов с последующей активацией услуги (svc_id)
                        $this->errors->set('Не возможно закрыть указанный счет');
                        $this->ajaxResponse(false);
                    } else {
                        # помечаем текущий баланс пользователя в закрываемом счете
                        $mUserBalance = array(
                            'user_id' => $aBill['user_id'],
                            'amount'  => $aBill['amount'],
                            'add'     => true
                        );
                    }
                    $bSuccess = $this->completeBill($nBillID, true, $mUserBalance);
                    if ($bSuccess) {
                        if ($bInPay) {
                            # обновляем баланс пользователя (зачисляем средства)
                            # в случае закрытия счета типа "пополнение счета"
                            $this->updateUserBalance($aBill['user_id'], $aBill['amount'], true);
                        }
                    }
                } else {
                    # отменяем счет
                    $bSuccess = $this->model->billSave($nBillID, array('status' => $nStatus));
                }

                $this->ajaxResponse(array('status' => $nStatus, 'success' => $bSuccess));
            }
            break;
            /**
             * Проверка состояния счета:
             * 1) webmoney - X18 интерфейс
             * 2) robox
             *
             * @param post ::bid $nBillID ID счета
             */
            case 'check':
            {
                if (!$this->haveAccessTo('manage')) {
                    $this->ajaxResponse(Errors::ACCESSDENIED);
                }

                if (!$nBillID) {
                    $this->ajaxResponse(Errors::IMPOSSIBLE);
                }

                $aBill = $this->model->billData($nBillID, array('psystem', 'psystem_way'));
                if (!$aBill) {
                    $this->ajaxResponse(Errors::IMPOSSIBLE);
                }

                $nPaySystem = (int)$aBill['psystem'];
                $sPaySystemWay = $aBill['psystem_way'];
                # проверяем включен ли данный способ оплаты
                if (!$this->isPaySystemAllowed($nPaySystem)) {
                    $this->ajaxResponse(Errors::IMPOSSIBLE);
                }

                switch ($nPaySystem) {
                    case self::PS_WM:
                    {
                        # Интерфейс запроса статуса платежа X18
                        $wmid = config::sys('bills.wm.id');
                        $lmi_payee_purse = $this->wm_purse($sPaySystemWay); // кошелек-получатель, на который совершался платеж

                        $md5 = strtoupper(md5($wmid . $lmi_payee_purse . $nBillID . $this->wm_purse($sPaySystemWay, true)));
                        # поскольку используется хеш, то 2 других метода авторизации - sign и secret_key - оставляем пустыми
                        $request = "<merchant.request>
                                      <wmid>$wmid</wmid>
                                      <lmi_payee_purse>$lmi_payee_purse</lmi_payee_purse>
                                      <lmi_payment_no>$nBillID</lmi_payment_no>
                                      <sign></sign><md5>$md5</md5><secret_key></secret_key>
                                    </merchant.request>";

                        $ch = curl_init("https://merchant.webmoney.ru/conf/xml/XMLTransGet.asp");
                        curl_setopt($ch, CURLOPT_HEADER, 0);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
//                        curl_setopt($ch, CURLOPT_CAINFO, "/path/to/verisign.cer");
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        $result = curl_exec($ch);
                        curl_close($ch);

                        $xmlres = simplexml_load_string($result); // смотрим результат выполнения запроса
                        $retval = strval($xmlres->retval);
                        if ($retval == -8) {
                            $sResponse = "Платеж №<strong>$nBillID</strong> не проводился";
                        } elseif ($retval != 0) {
                            // если результат не равен -8 и не равен 0, то возникла ошибка при обработке запроса
                            $sResponse = "Запрос составлен некорректно ($retval)";
                        } else { // если результат равен 0, то платеж с таким номером проведен
                            $wmtranid = strval($xmlres->operation->attributes()->wmtransid);
                            $date = strval($xmlres->operation->operdate);
                            $payer = strval($xmlres->operation->pursefrom);
                            $ip = strval($xmlres->operation->IPAddress);
                            $sResponse = "Платеж №<strong>$nBillID</strong> завершился успешно.<br />
                                   Он был произведен $date с кошелька $payer.<br />
                                   Плательщик использовал IP-адрес $ip.<br />
                                   WM-транзакции присвоен идентификатор $wmtranid.";
                        }
                        $this->ajaxResponse($sResponse);
                    }
                    break;
                    case self::PS_ROBOX:
                    {
                        $robox_login = config::sys('bills.robox.login');
                        $robox_pass2 = config::sys('bills.robox.pass2');
                        $request = 'https://merchant.roboxchange.com/WebService/Service.asmx/OpState?MerchantLogin=' . $robox_login . '&InvoiceID=' . $nBillID . '&Signature=' . md5($robox_login . ':' . $nBillID . ':' . $robox_pass2);

                        $ch = curl_init($request);
                        curl_setopt($ch, CURLOPT_HEADER, 0);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        $result = curl_exec($ch);
                        curl_close($ch);

                        /**
                         *
                         * <?xml version="1.0" encoding="utf-8" ?>
                         * <OperationStateResponse xmlns="http://merchant.roboxchange.com/WebService/">
                         * <Result>
                         * <Code>integer</Code>
                         * <Description>string</Description>
                         * </Result>
                         * <State>
                         * <Code>integer</Code>
                         * <RequestDate>datetime</RequestDate>
                         * <StateDate>datetime</StateDate>
                         * </State>
                         * <Info>
                         * <IncCurrLabel>string</IncCurrLabel>
                         * <IncSum>decimal</IncSum>
                         * <IncAccount>string</IncAccount>
                         * <PaymentMethod>
                         * <Code>string</Code>
                         * <Description>string</Description>
                         * </PaymentMethod>
                         * <OutCurrLabel>string</OutCurrLabel>
                         * <OutSum>decimal</OutSum>
                         * </Info>
                         * </OperationStateResponse>
                         */

                        $xml = simplexml_load_string($result); # смотрим результат выполнения запрос
                        if (empty($result)) {
                            $sResponse = 'Ошибка ответа сервера Robox';
                        } elseif (intval($xml->Result->Code) != 0) {
                            $sResponse = strval($xml->Result->Description);
                        } else {
                            # состояние счета
                            $sState = '?';
                            switch (intval($xml->State->Code)) {
                                case 5:
                                    $sState = 'Операция только инициализирована, деньги от покупателя не получены';
                                    break;
                                case 10:
                                    $sState = 'Операция отменена, деньги от покупателя не были получены';
                                    break;
                                case 50:
                                    $sState = 'Деньги от покупателя получены, производится зачисление денег на счет магазина';
                                    break;
                                case 60:
                                    $sState = 'Деньги после получения были возвращены покупателю';
                                    break;
                                case 80:
                                    $sState = 'Исполнение операции приостановлено';
                                    break;
                                case 100:
                                    $sState = 'Операция выполнена, завершена успешно';
                                    break;
                            }
                            $sResponse = 'Состояние: ' . $sState . ' (' . date('d.m.Y H:i:s', strtotime(strval($xml->State->StateDate))) . ')<br/>';

                            //информация об операции
                            $sResponse .= ' Способ оплаты: <strong>' . strval($xml->Info->PaymentMethod->Description) . '</strong>, <br/>
                                            Сумма уплаченная клиентом: <strong>' . strval($xml->Info->IncSum) . ' ' . strval($xml->Info->IncCurrLabel) . '</strong>, <br/>
                                            Аккаунт клиента в системе оплаты: <strong>' . strval($xml->Info->IncAccount) . '</strong>, <br/>
                                            Сумма отправленная ' . SITEHOST . ': <strong>' . strval($xml->Info->OutSum) . ' ' . strval($xml->Info->OutCurrLabel) . '</strong>';

                        }

                        $this->ajaxResponse($sResponse);
                    }
                    break;
                }
            }
            break;
            case 'extra':
            {
                if (!$nBillID) {
                    $this->ajaxResponse(Errors::IMPOSSIBLE);
                }

                $aData = $this->model->billData($nBillID, array('details'));

                $this->ajaxResponse(array('extra' => $aData['details']));
            }
            break;
        }

        $this->ajaxResponse(Errors::IMPOSSIBLE);
    }

}