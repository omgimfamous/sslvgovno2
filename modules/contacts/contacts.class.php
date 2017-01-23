<?php

class Contacts extends ContactsBase
{
    /**
     * Страница "Связаться с администрацией"
     */
    public function write()
    {
        # Использовать капчу, в случае если пользователь неавторизован
        $captcha_on = (bool)config::sys('contacts.captcha', TYPE_BOOL);

        $userID = User::id();
        $aData = array(
            'user'       => ($userID ? User::data(array('name', 'email')) : array('name' => '', 'email' => '')),
            'captcha_on' => $captcha_on,
        );

        if (Request::isAJAX()) {
            $response = array('captcha' => false);
            $p = $this->input->postm(array(
                    'name'    => array(TYPE_NOTAGS, 'len' => 70), # имя
                    'email'   => array(TYPE_NOTAGS, 'len' => 70), # e-mail
                    'ctype'   => TYPE_UINT, # тип контакта
                    'message' => TYPE_NOTAGS, # сообщение
                    'captcha' => TYPE_STR, # капча
                )
            );

            if (!$userID) {
                if (empty($p['name'])) {
                    $this->errors->set(_t('contacts', 'Укажите ваше имя'), 'email');
                }
                if (!$this->input->isEmail($p['email'])) {
                    $this->errors->set(_t('contacts', 'E-mail адрес указан некорректно'), 'email');
                }
            }

            $p['message'] = $this->input->cleanTextPlain($p['message'], 3000, false);
            if (mb_strlen($p['message']) < 10) {
                $this->errors->set(_t('contacts', 'Текст сообщения слишком короткий'), 'message');
            }

            if (!$userID && $captcha_on) {
                # проверяем капчу
                if (!CCaptchaProtection::correct($this->input->cookie('c2'), $p['captcha'])) {
                    $this->errors->set(_t('', 'Результат с картинки указан некорректно'), 'captcha');
                    $response['captcha'] = true;
                }
            } else {
                # не чаще чем раз в {X} секунд с одного IP (для одного пользователя)
                if ($this->errors->no()) {
                    Site::i()->preventSpam('contacts-form');
                }
            }

            if ($this->errors->no()) {
                Request::deleteCOOKIE('c2');
                unset($p['captcha']);

                # корректируем тип контакта
                $contactTypes = $this->getContactTypes();
                if (!array_key_exists($p['ctype'], $contactTypes)) {
                    $p['ctype'] = key($contactTypes);
                }

                $nContactID = $this->model->contactSave(0, $p);
                if ($nContactID) {
                    $this->updateCounter($p['ctype'], 1);

                    bff::sendMailTemplate(array(
                            'name'    => $p['name'],
                            'email'   => $p['email'],
                            'message' => nl2br($p['message']),
                        ),
                        'contacts_admin', config::sys('mail.admin')
                    );
                }
            }

            $this->ajaxResponseForm($response);
        }

        # SEO: Форма контактов
        $this->urlCorrection(static::url('form'));
        $this->seo()->canonicalUrl(static::url('form', array(), true));
        $this->seo()->setPageMeta('site', 'contacts-form', array(), $aData);

        $aData['types'] = $this->getContactTypes(true);

        return $this->viewPHP($aData, 'write');
    }

}