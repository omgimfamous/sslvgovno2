<?php

/**
 * Класс отправки SMS уведомлений пользователям
 */
class UsersSMS extends Component
{
    protected $userErrors = true;

    public function __construct()
    {
        $this->init();
    }

    /**
     * Отправка SMS с кодом активации для подтверждения телефона
     * @param string $phoneNumber номер телефона в международном формате
     * @param string $code код активации
     * @return bool результат отправки: true - отправлено false - нет
     */
    public function sendActivationCode($phoneNumber, $code)
    {
        # Считаем кол-во попыток отправки
        if ($this->retryCounter('users-sms-activation-code', $this->userErrors)) {
            return false;
        }

        # Формируем текст сообщения
        $message = _t('users', 'Код активации: [code]', array(
            'site.title' => config::sys('site.title'),
            'site.host'  => config::sys('site.host'),
            'code'       => $code,
        ));
        if (BFF_LOCALHOST) {
            bff::log(array('phone' => $phoneNumber, 'message' => $message, 'code' => $code));
            return true;
        }

        # Отправляем сообщение на телефон
        return $this->send($phoneNumber, $message);
    }

    /**
     * Отправка SMS сообщения на указанный номер
     * Провайдер указывается в системной настройке 'users.sms.provider'
     * @param string $phoneNumber номер телефона в международном формате
     * @param string $message текст сообщения
     * @return bool результат отправки: true - отправлено false - нет
     */
    public function send($phoneNumber, $message)
    {
        if (!$this->input->isPhoneNumber($phoneNumber)) {
            if ($this->userErrors) $this->errors->set(_t('users', 'Неправильно указан номер телефона'));
            return false;
        }

        # Провайдер
        switch (config::sys('users.sms.provider', TYPE_STR))
        {
            # Сервис sms.ru
            case 'sms_ru': {
                return $this->send_sms_ru($phoneNumber, $message);
            } break;
            # Провайдер не был указан либо указан некорректно
            default: {
                bff::log('UsersSMS: Hет доступных сервисов для отправки SMS');
                return false;
            } break;
        }
    }

    /**
     * Отправить SMS с помощью сервиса sms.ru
     * Доступные системные настройки:
     *    'users.sms.sms_ru.api_id' => 'ключ API',
     *    'users.sms.sms_ru.from' => 'Имя',
     *    'users.sms.sms_ru.test' => true / false,
     * @param string $phoneNumber номер телефона в международном формате
     * @param string $message текст сообщения
     * @return bool результат отправки true - отправлено false - нет
     */
    protected function send_sms_ru($phoneNumber, $message)
    {
        $appID = config::sys('users.sms.sms_ru.api_id', TYPE_STR);
        if (empty($appID)) {
            bff::log('UsersSMS: Необходимо указать системную настройку "users.sms.sms_ru.api_id"');
            return false;
        }

        # Выполняем отправку
        $ch = curl_init('http://sms.ru/sms/send');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array(
            'test'   => config::sys('users.sms.sms_ru.test', 0),
            'api_id' => $appID,
            'to'     => $phoneNumber,
            'from'   => config::sys('users.sms.sms_ru.from', TYPE_STR),
            'text'   => $message,
        ));
        $body = curl_exec($ch);
        curl_close($ch);

        $response = explode("\n", strval($body));
        if (!empty($response)) {
            foreach ($response as $k=>$v) {
                $response[$k] = trim($v);
            }
        } else {
            $response = array(0=>'');
        }

        # Отправка выполнена успешно, код ответа - 100
        if (strpos('100', $response[0]) !== false) {
            return true;
        }

        # Ошибка отправки
        switch (intval($response[0])) {
            case 200: $error = 'Неправильный api_id'; break;
            case 201: $error = 'Не хватает средств на лицевом счету'; break;
            case 202: $error = 'Неправильно указан получатель';
                if($this->userErrors) $this->errors->set(_t('users', 'Неправильно указан номер телефона'));
                break;
            case 203: $error = 'Нет текста сообщения'; break;
            case 204: $error = 'Имя отправителя не согласовано с администрацией'; break;
            case 205: $error = 'Сообщение слишком длинное (превышает 8 СМС)'; break;
            case 206: $error = 'Будет превышен или уже превышен дневной лимит на отправку сообщений'; break;
            case 207: $error = 'На этот номер (или один из номеров) нельзя отправлять сообщения, либо указано более 100 номеров в списке получателей';
                if($this->userErrors) $this->errors->set(_t('users', 'Неправильно указан номер телефона'));
                break;
            case 208: $error = 'Параметр time указан неправильно'; break;
            case 209: $error = 'Вы добавили этот номер (или один из номеров) в стоп-лист'; break;
            case 210: $error = 'Используется GET, где необходимо использовать POST'; break;
            case 211: $error = 'Метод не найден'; break;
            case 212: $error = 'Текст сообщения необходимо передать в кодировке UTF-8 (вы передали в другой кодировке)'; break;
            case 220: $error = 'Сервис временно недоступен, попробуйте чуть позже.';
                if($this->userErrors) $this->errors->set(_t('users', 'Сервис временно недоступен, попробуйте чуть позже'));
                break;
            case 230: $error = 'Сообщение не принято к отправке, так как на один номер в день нельзя отправлять более 60 сообщений.'; break;
            case 300: $error = 'Неправильный token (возможно истек срок действия, либо ваш IP изменился)'; break;
            case 301: $error = 'Неправильный пароль, либо пользователь не найден'; break;
            case 302: $error = 'Пользователь авторизован, но аккаунт не подтвержден (пользователь не ввел код, присланный в регистрационной смс)'; break;
            default:  $error = 'Неизвестный код ошибки';
        }
        bff::log('UsersSMS: ошибка отправки sms.ru #'.strval($response[0]).' - '.$error);
        bff::log($body);

        return false;
    }

    /**
     * Ограничение кол-ва попыток с последующей паузой
     * @param string $actionKey тип действия
     * @param bool $setErrors устанавливать ошибку
     * @return bool true - достигнут лимит повторов
     */
    protected function retryCounter($actionKey, $setErrors = true)
    {
        return Site::i()->preventSpamCounter($actionKey,
                    config::sys('users.sms.retry.limit', 3),
                    (config::sys('users.sms.retry.timeout', 3) * 60),
                    $setErrors);
    }

    /**
     * Фиксировать ошибки для пользователей
     * @param boolean $enabled true - фиксировать
     */
    public function userErrorsEnabled($enabled)
    {
        $this->userErrors = $enabled;
    }

}