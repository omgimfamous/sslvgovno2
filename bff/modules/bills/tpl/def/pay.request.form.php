<?php

/**
 * @var $this Bills
 * @var $psystem integer тип системы оплаты
 * @var $psystem_way string способ оплаты в рамках системы оплаты
 * @var $bill_id integer ID счета
 * @var $bill_description string описание (название) счета
 * @var $amount integer сумма счета
 * @var $extra array доп. параметры
 */

switch($psystem)
{
    # Webmoney (http://www.webmoney.ru/)
    case Bills::PS_WM:
    {
        echo '<form accept-charset="cp1251" method="POST" action="https://merchant.webmoney.ru/lmi/payment.asp">
                <input type="hidden" name="LMI_PAYMENT_AMOUNT" type="hidden" value="'.$amount.'" />
                <input type="hidden" name="LMI_PAYMENT_DESC" value="'.$bill_description.'" />
                <input type="hidden" name="LMI_PAYMENT_NO" value="'.$bill_id.'" />
                <input type="hidden" name="LMI_PAYEE_PURSE" value="'.$this->wm_purse($psystem_way).'" />
                <input type="hidden" name="LMI_SIM_MODE" value="0" />
                '.(!empty($wm_result)?'<input type="hidden" name="LMI_RESULT_URL" value="'.$wm_result.'" />':'').'
                '.(!empty($wm_success)?'<input type="hidden" name="LMI_SUCCESS_URL" value="'.$wm_success.'" />':'').'
                '.(!empty($wm_success_method)?'<input type="hidden" name="LMI_SUCCESS_METHOD" value="'.$wm_success_method.'" />':'').'
                '.(!empty($wm_fail)?'<input type="hidden" name="LMI_FAIL_URL" value="'.$wm_fail.'" />':'').'
                '.(!empty($wm_fail_method)?'<input type="hidden" name="LMI_FAIL_METHOD" value="'.$wm_fail_method.'" />':'').'
              </form>';
    } break;


    # Robokassa (http://www.roboxchange.com)
    case Bills::PS_ROBOX:
    {
        // Подсчёт Robox CRC
        $robox_crc = md5($robox_login.':'.$amount.':'.$bill_id.':'.$robox_pass1);

        $robox_url = 'https://merchant.roboxchange.com/Index.aspx';
        if($robox_test) {
            // тестовый сервер
            $robox_url = 'http://test.robokassa.ru/Index.aspx';
        }

        echo '<form action="'.$robox_url.'" method="POST">
                <input type="hidden" name="MrchLogin" value="'.$robox_login.'" />
                <input type="hidden" name="OutSum" value="'.$amount.'" />
                <input type="hidden" name="InvId" value="'.$bill_id.'" />
                <input type="hidden" name="Desc" value="'.$bill_description.'" />
                <input type="hidden" name="SignatureValue" value="'.$robox_crc.'" />
                <input type="hidden" name="IncCurrLabel" value="" />
                <input type="hidden" name="Culture" value="Ru" />
            </form>';
    } break;


    # W1 (http://www.w1.ru/)
    case Bills::PS_W1:
    {
        $fields = array(
            'WMI_MERCHANT_ID' => $w1_id,
            'WMI_PAYMENT_AMOUNT' => $WMI_PAYMENT_AMOUNT,
            'WMI_PAYMENT_NO'  => $bill_id,
            'WMI_CURRENCY_ID' => $w1_currency,
            'WMI_DESCRIPTION' => $bill_description,
          'WMI_PTENABLED'      => 'WalletOneEUR',
  
        );
        # Формируем SUCCESS_URL:
        # 1) из указанного при инициализации формы ($extra['success'])
        # 2) из указанного в настройках модуля (w1_success)
        if( ! empty($extra['success']) ) {
            $w1_success = $extra['success'];
        }
        if( ! empty($w1_success) ) {
            $fields['WMI_SUCCESS_URL'] = $w1_success;
        }

        if( ! empty($w1_fail) ) {
            $fields['WMI_FAIL_URL'] = $w1_fail;
        }

        # Помечаем доступные способы оплаты W1:
        # 1) из указанных при инициализации формы $psystem_way
        # 2) из указанных в настройках модуля (w1_ways)
        $w1_ways = ( ! empty($psystem_way) ? $psystem_way :
                     ( ! empty($w1_ways) ? $w1_ways : false) );

        if( ! empty($w1_ways) ) {
            if( is_array($w1_ways) ) {
                $fields['WMI_PTENABLED'] = $w1_ways;
            }
        }

        $fields['WMI_SIGNATURE'] = $this->w1_signature( $fields );

        echo '<form action="https://wl.walletone.com/checkout/checkout/index" method="POST" accept-charset="UTF-8">';
        foreach($fields as $key => $val)
        {
            if (is_array($val)) {
               foreach($val as $value) {
                    echo '<input type="text" name="'.$key.'" value="'.$value.'" /><br />';
               }
            } else {
               echo '<input type="text" name="'.$key.'" value="'.$val.'" /><br />';
            }
        }
        echo '</form>';
    } break;
}