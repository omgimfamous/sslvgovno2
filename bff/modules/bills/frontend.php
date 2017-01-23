<?php

/**
 * Абстрактные методы:
 * - processPayRequest
 * - processBill
 */

abstract class BillsModule extends BillsModuleBase
{
    /**
     * Метод принимающий запрос от платежной системы
     * и выполняющий обработчик данной системы оплаты (*_request),
     * который в свою очередь, при успешной оплате счета, вызывает Bills::processBill
     * @return mixed
     */
    public abstract function processPayRequest();

    /**
     * Оплата счёта на основе данных от платёжной системы
     * @param int $nBillID ID счета (в таблице TABLE_BILLS)
     * @param float|int $fMoney сумма счета (оплачиваемая)
     * @param int $nPaySystem ID системы оплаты Bills::PS_
     * @param mixed $mDetails детали от платежной системы
     * @param array $mExtra доп.параметры (если необходимо)
     * @return mixed
     */
    protected abstract function processBill($nBillID, $fMoney = 0, $nPaySystem = 0, $mDetails = false, $aExtra = array());

    # --------------------------------------------------------------------
    # Система оплаты WebMoney

    protected function wm_request()
    {
        extract($_POST);

        $nBillID = intval($LMI_PAYMENT_NO);
        $aBill = $this->model->billData($nBillID, array('money', 'psystem_way'));

        if (isset($LMI_PREREQUEST) && $LMI_PREREQUEST == 1) # предварительный запрос
        {
            if (!$nBillID || empty($aBill)) {
                $this->wm_response('');
            }

            # Проверка суммы
            $LMI_PAYMENT_AMOUNT = floatval(trim($LMI_PAYMENT_AMOUNT));
            if ($aBill['money'] != $LMI_PAYMENT_AMOUNT) # сумма, которую пытается заплатить не равна указанной в счете
            {
                $this->wm_response('Неверная сумма ' . $LMI_PAYMENT_AMOUNT);
            }

            $sPurseCorrect = $this->wm_purse($aBill['psystem_way']);

            if ($sPurseCorrect != trim($LMI_PAYEE_PURSE)) {
                $this->wm_response('Неверный кошелек получателя ' . $LMI_PAYEE_PURSE);
            }

            $this->wm_response(true);

        } else {

            if (!$nBillID || empty($aBill)) {
                $this->log('Webmoney: id платежа указан некорректно: "' . $nBillID . '"');

                return $this->payError('no_params');
            }

            if (empty($LMI_HASH)) {
                $this->log('Webmoney: параметр LMI_HASH пустой: "' . $LMI_HASH . '"');

                return $this->payError('no_params');
            }

            $str = $LMI_PAYEE_PURSE . $LMI_PAYMENT_AMOUNT . $LMI_PAYMENT_NO . $LMI_MODE . $LMI_SYS_INVS_NO . $LMI_SYS_TRANS_NO .
                $LMI_SYS_TRANS_DATE . $this->wm_purse($aBill['psystem_way'], true) . $LMI_PAYER_PURSE . $LMI_PAYER_WM;

            if (mb_strtolower($LMI_HASH) === mb_strtolower(hash('sha256', $str))) {
                $mResult = $this->processBill($nBillID, $LMI_PAYMENT_AMOUNT, self::PS_WM);
                if ($mResult !== true) {
                    return $mResult;
                }
            } else {
                $this->log('Webmoney: неверная контрольная сумма "' . mb_strtolower($LMI_HASH) . '" !== "' . mb_strtolower(hash('sha256', $str)) . '"');

                return $this->payError('crc_error');
            }
        }

        return true;
    }

    protected function wm_response($mResponse)
    {
        if (is_string($mResponse)) {
            echo 'ERR: ' . $mResponse;
            exit;
        } else {
            echo 'YES';
            exit;
        }
    }

    # --------------------------------------------------------------------
    # Система оплаты RBKMoney

    protected function rbkmoney_request()
    {
        extract($_POST);

        if (empty($hash)) {
            $this->log('RBKMoney: параметр hash пустой: "' . $hash . '"');

            return $this->payError('no_params');
        }

        if ($orderId <= 0) {
            $this->log('RBKMoney: Некорректный номер счета, (#' . $orderId . ')');

            return $this->payError('wrong_bill_id');
        }

        if ($paymentStatus == 3) # статус RBKMoney: Платеж принят на обработку
        {
            $this->model->billSave($orderId, array('status' => self::STATUS_PROCESSING));

            return 'OK';
        } elseif ($paymentStatus == 5) # статус RBKMoney: Платеж зачислен
        {
            $str = config::sys('bills.rbkmoney.id') . "::$orderId::$serviceName::$eshopAccount::$recipientAmount::$recipientCurrency::$paymentStatus::$userName::$userEmail::$paymentData::" . config::sys('bills.rbkmoney.key');
            if (mb_strtolower($hash) === mb_strtolower(md5($str))) {
                $mResult = $this->processBill($orderId, $recipientAmount, self::PS_RBK);

                return ($mResult === true ? 'OK' : $mResult);
            } else {
                $this->log('RBKMoney: неверная контрольная сумма "' . mb_strtolower($hash) . '" !== "' . mb_strtolower(md5($str)) . '"');

                return $this->payError('crc_error');
            }
        }

        return $this->payError('pay_error');
    }

    # --------------------------------------------------------------------
    # Система оплаты Robox

    protected function robox_request()
    {
        $OutSum = (!empty($_REQUEST['OutSum']) ? $_REQUEST['OutSum'] : '');
        $InvId = (!empty($_REQUEST['InvId']) ? $_REQUEST['InvId'] : '');
        $crc = (!empty($_REQUEST['SignatureValue']) ? $_REQUEST['SignatureValue'] : '');

        if (empty($crc)) {
            $this->log('Robox: параметр SignatureValue пустой: "' . $crc . '"');

            return $this->payError('no_params');
        }

        if (!is_numeric($InvId)) {
            $this->log('Robox: Некорректный номер счета, (#' . $InvId . ')');

            return $this->payError('wrong_bill_id');
        }

        $pass = config::sys('bills.robox.pass2');
        $crc2 = strtoupper(md5("$OutSum:$InvId:$pass"));

        if (strtoupper($crc) === $crc2) {
            $mResult = $this->processBill($InvId, $OutSum, self::PS_ROBOX);
            if ($mResult === true) {
                echo "OK$InvId" . PHP_EOL;
                exit;
            } else {
                return $mResult;
            }
        } else {
            $this->log('Robox: неверная контрольная сумма "' . strtoupper($crc) . '" !== "' . $crc2 . '"');

            return $this->payError('crc_error');
        }
    }

    # --------------------------------------------------------------------
    # Система оплаты Z-Payment

    protected function zpay_request()
    {
        extract($_POST);

        $zpayid = config::sys('bills.zpay.id'); # $LMI_PAYEE_PURSE
        $zpkey = config::sys('bills.zpay.key'); # $LMI_SECRET_KEY

        if (empty($LMI_HASH)) {
            $this->log('Z-Payment: параметр LMI_HASH пустой: "' . $LMI_HASH . '"');

            return $this->payError('no_params');
        }

        /*
            ID магазина (LMI_PAYEE_PURSE);
            Сумма платежа (LMI_PAYMENT_AMOUNT);
            Внутренний номер покупки продавца (LMI_PAYMENT_NO);
            Флаг тестового режима (LMI_MODE);
            Внутренний номер счета в системе Z-PAYMENT (LMI_SYS_INVS_NO);
            Внутренний номер платежа в системе Z-PAYMENT (LMI_SYS_TRANS_NO);
            Дата и время выполнения платежа (LMI_SYS_TRANS_DATE);
            Merchant Key (LMI_SECRET_KEY);
            Кошелек покупателя в системе Z-PAYMENT или его e-mail (LMI_PAYER_PURSE);
            Кошелек покупателя в системе Z-PAYMENT или его e-mail (LMI_PAYER_WM).
        */

        $str = "{$LMI_PAYEE_PURSE}{$LMI_PAYMENT_AMOUNT}{$LMI_PAYMENT_NO}{$LMI_MODE}{$LMI_SYS_INVS_NO}" .
            "{$LMI_SYS_TRANS_NO}{$LMI_SYS_TRANS_DATE}{$LMI_SECRET_KEY}{$LMI_PAYER_PURSE}{$LMI_PAYER_WM}";

        if (mb_strtolower($LMI_HASH) === mb_strtolower(md5($str))) {
            return $this->processBill($LMI_PAYMENT_NO, $LMI_PAYMENT_AMOUNT, self::PS_ZPAY);
        } else {
            $this->log('Z-Payment: неверная контрольная сумма "' . mb_strtolower($LMI_HASH) . '" !== "' . mb_strtolower(md5($str)) . '"');

            return $this->payError('crc_error');
        }
    }

    # --------------------------------------------------------------------
    # Система оплаты W1

    protected function w1_request()
    {
        // чистим все поля, которые не начинаются на WMI_
        foreach ($_POST as $k => $v) {
            if (strpos($k, 'WMI_') !== 0) {
                unset($_POST[$k]);
            }
        }
        extract($_POST);

        if (empty($WMI_SIGNATURE)) {
            $this->w1_response(false, 'Отсутствует параметр WMI_SIGNATURE');
        }
        if (empty($WMI_PAYMENT_NO)) {
            $this->w1_response(false, 'Отсутствует параметр WMI_PAYMENT_NO');
        }
        if (!isset($WMI_ORDER_STATE)) {
            $this->w1_response(false, 'Отсутствует параметр WMI_ORDER_STATE');
        }

        # Проверяем подпись
        $crc = $WMI_SIGNATURE;
        unset($_POST['WMI_SIGNATURE']);
        $crc2 = $this->w1_signature($_POST, false);
        if ($crc !== $crc2) {
            $this->log('W1: неверная контрольная сумма "' . $crc . '" !== "' . $crc2 . '"');
            $this->w1_response(false, $this->payError('crc_error'));
        }

        # Проверяем состояние счета (в ответе W1 корректно только ACCEPTED)
        if (strtoupper($WMI_ORDER_STATE) !== 'ACCEPTED') {
            $this->log('W1: неверное состояние(ORDER_STATE) "' . $WMI_ORDER_STATE . '" !== "ACCEPTED"');
            $this->w1_response(false, 'Неверное состояние(WMI_ORDER_STATE)');
        }

        # Обрабатываем счет
        $mResult = $this->processBill($WMI_PAYMENT_NO, $WMI_PAYMENT_AMOUNT, self::PS_W1, array(
                'WMI_ORDER_ID'       => (isset($WMI_ORDER_ID) ? $WMI_ORDER_ID : ''),
                'WMI_PAYMENT_AMOUNT' => $WMI_PAYMENT_AMOUNT,
                'WMI_PAYMENT_TYPE'   => (isset($WMI_PAYMENT_TYPE) ? $WMI_PAYMENT_TYPE : ''),
                'WMI_CURRENCY_ID'    => $WMI_CURRENCY_ID,
                'WMI_TO_USER_ID'     => (isset($WMI_TO_USER_ID) ? $WMI_TO_USER_ID : ''),
                'WMI_CREATE_DATE'    => $WMI_CREATE_DATE,
                'WMI_UPDATE_DATE'    => $WMI_UPDATE_DATE,
            )
        );
        if ($mResult === true) {
            $this->w1_response('OK');
        } else {
            $this->w1_response(false, $mResult);
        }
    }

    protected function w1_signature($aFields, $bEncode = true)
    {
        # Сортировка значений внутри полей
        foreach ($aFields as $name => $val) {
            if (is_array($val)) {
                usort($val, "strcasecmp");
                $aFields[$name] = $val;
            }
        }

        # Формирование сообщения, путем объединения значений формы,
        # отсортированных по именам ключей в порядке возрастания.
        # Конвертация из текущей кодировки (UTF-8)
        # необходима только если кодировка магазина отлична от Windows-1251
        uksort($aFields, 'strcasecmp');

        $fieldValues = '';
        foreach ($aFields as $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    if ($bEncode) {
                        $v = iconv('utf-8', 'windows-1251', $v);
                    }
                    $fieldValues .= $v;
                }
            } else {
                if ($bEncode) {
                    $value = iconv('utf-8', 'windows-1251', $value);
                }
                $fieldValues .= $value;
            }
        }

        # Формирование значения параметра WMI_SIGNATURE, путем
        # вычисления отпечатка, сформированного выше сообщения,
        # по алгоритму MD5 и представление его в Base64
        return base64_encode(pack("H*", md5($fieldValues . config::sys('bills.w1.secret'))));
    }

    protected function w1_response($sResult = 'OK', $sDescription = false)
    {
        if (empty($sResult)) {
            $sResult = 'RETRY';
        }
        echo 'WMI_RESULT=' . strtoupper($sResult);
        if ($sDescription !== false) {
            echo '&WMI_DESCRIPTION=' . urlencode($sDescription);
        }
        exit;
    }

}