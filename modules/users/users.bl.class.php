<?php

abstract class UsersBase extends UsersModule
{
    /** @var UsersModel */
    public $model = null;
    /** @var bool задействовать поле "пол" */
    public $profileSex = false;

    # Флаги email-уведомлений:
    const ENOTIFY_NEWS = 1; # новости сервиса
    const ENOTIFY_INTERNALMAIL = 2; # уведомления о новых сообщениях
    const ENOTIFY_BBS_COMMENTS = 4; # уведомления о комментариях в объявлениях

    public function init()
    {
        parent::init();

        bff::autoloadEx(array(
                'UsersSMS'   => array('app', 'modules/users/users.sms.php'),
            )
        );

        # кол-во доступных номеров телефон (в профиле)
        $this->profilePhonesLimit = config::sys('users.profile.phones', 5);
    }

    public function sendmailTemplates()
    {
        $templates = array(
            'users_register'      => array(
                'title'       => 'Пользователи: уведомление о регистрации',
                'description' => 'Уведомление, отправляемое <u>пользователю</u> после регистрации, с указаниями об активации аккаунта',
                'vars'        => array(
                    '{email}'         => 'Email',
                    '{password}'      => 'Пароль',
                    '{activate_link}' => 'Ссылка активации аккаунта'
                )
            ,
                'impl'        => true,
                'priority'    => 1,
            ),
            'users_register_phone' => array(
                'title'       => 'Пользователи: уведомление о регистрации (с вводом номера телефона)',
                'description' => 'Шаблон письма, отправляемого <u>пользователю</u> после успешной регистрации с подтверждением номера телефона',
                'vars'        => array(
                    '{email}'         => 'Email',
                    '{password}'      => 'Пароль',
                    '{phone}'         => 'Номер телефона',
                ),
                'impl'        => true,
                'priority'    => 1.5,
            ),
            'users_register_auto' => array(
                'title'       => 'Пользователи: уведомление об успешной автоматической регистрации',
                'description' => 'Уведомление, отправляемое <u>пользователю</u> в случае автоматической регистрации.<br /> Активация объявления / переход по ссылке "продолжить переписку"',
                'vars'        => array('{name}' => 'Имя', '{email}' => 'Email', '{password}' => 'Пароль')
            ,
                'impl'        => true,
                'priority'    => 2,
            ),
            'users_forgot_start'  => array(
                'title'       => 'Пользователи: восстановление пароля',
                'description' => 'Уведомление, отправляемое <u>пользователю</u> в случае запроса на восстановление пароля',
                'vars'        => array(
                    '{name}'  => 'Имя',
                    '{email}' => 'Email пользователя',
                    '{link}'  => 'Ссылка восстановления'
                )
            ,
                'impl'        => true,
                'priority'    => 3,
            ),
            'users_blocked'       => array(
                'title'       => 'Пользователи: уведомление о блокировке аккаунта',
                'description' => 'Уведомление, отправляемое <u>пользователю</u> в случае блокировки аккаунта',
                'vars'        => array(
                    '{name}'           => 'Имя',
                    '{email}'          => 'Email',
                    '{blocked_reason}' => 'Причина блокировки'
                )
            ,
                'impl'        => true,
                'priority'    => 4,
            ),
            'users_unblocked'     => array(
                'title'       => 'Пользователи: уведомление о разблокировке аккаунта',
                'description' => 'Уведомление, отправляемое <u>пользователю</u> в случае разблокировки аккаунта',
                'vars'        => array('{name}' => 'Имя', '{email}' => 'Email')
            ,
                'impl'        => true,
                'priority'    => 5,
            ),
        );

        if (!static::registerPhone()) {
            unset($templates['users_register_phone']);
        }

        return $templates;
    }

    /**
     * Формирование URL
     * @param string $key ключ
     * @param array $opts параметры
     * @param boolean $dynamic динамическая ссылка
     * @return string
     */
    public static function url($key = '', array $opts = array(), $dynamic = false)
    {
        $base = static::urlBase(LNG, $dynamic);
        switch ($key) {
            # Авторизация
            case 'login':
                return $base . '/user/login' . static::urlQuery($opts);
                break;
            # Выход
            case 'logout':
                return $base . '/user/logout' . static::urlQuery($opts);
                break;
            # Регистрация
            case 'register':
                return $base . '/user/register' . (!empty($opts) ? static::urlQuery($opts) : '');
                break;
            # Авторизация
            case 'login.social':
                return $base . '/user/loginsocial/' . (!empty($opts['provider']) ? $opts['provider'] : '') . static::urlQuery($opts, array('provider'));
                break;
            # Профиль пользователя
            case 'user.profile':
                return $base . '/users/' . $opts['login'] . '/' . (!empty($opts['tab']) ? $opts['tab'] . '/' : '') . static::urlQuery($opts, array('login','tab'));
                break;
            # Восстановление пароля
            case 'forgot':
                return $base . '/user/forgot' . (!empty($opts) ? static::urlQuery($opts) : '');
                break;
            # Настройки профиля
            case 'my.settings':
                return $base . '/cabinet/settings' . (!empty($opts) ? static::urlQuery($opts) : '');
                break;
            # Ссылка активации акканута
            case 'activate':
                return $base . '/user/activate' . (!empty($opts) ? static::urlQuery($opts) : '');
                break;
            # Пользовательское соглашение
            case 'agreement':
                return $base . '/'.config::sys('users.agreement.page', 'agreement.html', TYPE_STR) . (!empty($opts) ? static::urlQuery($opts) : '');
                break;
        }

        return $base;
    }

    /**
     * Страница просмотра профиля пользователя
     * @param string $login логин пользователя
     * @param string $tab ключ подраздела
     * @param array $opts доп.параметры
     * @param boolean $dynamic динамическая ссылка
     * @return string
     */
    public static function urlProfile($login, $tab = '', array $opts = array(), $dynamic = false)
    {
        if ($tab == 'items') {
            $tab = '';
        }

        return static::url('user.profile', array('login' => $login, 'tab' => $tab) + $opts, $dynamic);
    }

    /**
     * Описание seo шаблонов страниц
     * @return array
     */
    public function seoTemplates()
    {
        return array(
            'pages' => array(
                'login'    => array(
                    't'      => 'Авторизация',
                    'list'   => false,
                    'i'      => true,
                    'macros' => array()
                ),
                'register' => array(
                    't'      => 'Регистрация',
                    'list'   => false,
                    'i'      => true,
                    'macros' => array()
                ),
                'forgot'   => array(
                    't'      => 'Забыли пароль',
                    'list'   => false,
                    'i'      => true,
                    'macros' => array()
                ),
            ),
        );
    }

    /**
     * Валидация данных пользователя
     * @param array $aData @ref данные
     * @param array|boolean $mKeys список ключей требующих валидации данных или TRUE - все
     * @param array $aExtraSettings дополнительные параметры валидации
     */
    public function cleanUserData(array &$aData, $mKeys = true, array $aExtraSettings = array())
    {
        if (!is_array($mKeys)) {
            $mKeys = array_keys($aData);
        }

        foreach ($mKeys as $key) {
            if (!isset($aData[$key])) {
                continue;
            }
            switch ($key) {
                case 'name': # имя
                    # допустимые символы:
                    # латиница, кирилица, тире, пробелы
                    $aData[$key] = preg_replace('/[^a-zA-Z0-9\w\-\s]+/iu', '', $aData[$key]);
                    $aData[$key] = trim(mb_substr($aData[$key], 0, (isset($aExtraSettings['name_length']) ? $aExtraSettings['name_length'] : 50)), '- ');
                    break;
                case 'birthdate': # дата рождения
                    if (!$this->profileBirthdate) {
                        break;
                    }

                    if (empty($aData[$key]) || !checkdate($aData[$key]['month'], $aData[$key]['day'], $aData[$key]['year'])) {
                        $this->errors->set(_t('users', 'Дата рождения указана некорректно'));
                    } else {
                        $aData[$key] = "{$aData[$key]['year']}-{$aData[$key]['month']}-{$aData[$key]['day']}";
                    }
                    break;
                case 'site': # сайт
                    if (mb_strlen($aData[$key]) > 3) {
                        $aData[$key] = mb_substr($aData[$key], 0, 255);
                        if (stripos($aData[$key], 'http') !== 0) {
                            $aData[$key] = 'http://' . $aData[$key];
                        }
                    } else {
                        $aData[$key] = '';
                    }
                    break;
                case 'about': # о себе
                    $aData[$key] = mb_substr($aData[$key], 0, 2500);
                    break;
                case 'phones': # телефоны
                    # в случае если телефоны в serialized виде => пропускаем
                    if (is_string($aData[$key]) && mb_stripos($aData[$key], 'a:') === 0) {
                        break;
                    }
                    $aPhones = self::validatePhones($aData[$key], (isset($aExtraSettings['phones_limit']) ? $aExtraSettings['phones_limit'] : $this->profilePhonesLimit));
                    $aData[$key] = serialize($aPhones);
                    # сохраняем первый телефон в отдельное поле
                    if (!empty($aPhones)) {
                        $aPhoneFirst = reset($aPhones);
                        $aData['phone'] = $aPhoneFirst['v'];
                    } else {
                        $aData['phone'] = '';
                    }
                    break;
                case 'skype': # skype
                    $aData[$key] = preg_replace('/[^\.\s\[\]\:\-\_a-zA-Z0-9]/', '', $aData[$key]);
                    $aData[$key] = trim(mb_substr($aData[$key], 0, 32), ' -');
                    break;
                case 'icq': # icq 
                    $aData[$key] = preg_replace('/[^\.\-\s\_0-9]/', '', $aData[$key]);
                    $aData[$key] = trim(mb_substr($aData[$key], 0, 20), ' .-');
                    break;
                case 'region_id':
                    if ($aData[$key] > 0) {
                        # проверяем корректность указанного города
                        if (!Geo::isCity($aData[$key])) {
                            $aData[$key] = 0;
                            $aData['reg1_country'] = 0;
                            $aData['reg2_region'] = 0;
                            $aData['reg3_city'] = 0;
                        }else{
                            # разворачиваем данные о регионе: region_id => reg1_country, reg2_region, reg3_city
                            $aRegions = Geo::model()->regionParents($aData['region_id']);
                            $aData = array_merge($aData, $aRegions['db']);
                        }
                    }else{
                        $aData['reg1_country'] = 0;
                        $aData['reg2_region'] = 0;
                        $aData['reg3_city'] = 0;
                    }
                    break;
            }
        }
    }

    /**
     * Иницилизация компонента работы с соц. аккаунтами
     * @return UsersSocial
     */
    public function social()
    {
        static $i;
        if (!isset($i)) {
            $i = new UsersSocial();
        }

        return $i;
    }

    /**
     * SMS шлюз
     * @param boolean $userErrors фиксировать ошибки для пользователей
     * @return UsersSMS
     */
    public function sms($userErrors = true)
    {
        static $i;
        if (!isset($i)) {
            $i = new UsersSMS();
        }
        $i->userErrorsEnabled($userErrors);

        return $i;
    }

    /**
     * Запрашивать номер телефона пользователя при регистрации
     * @return bool
     */
    public static function registerPhone()
    {
        return (bool)config::sys('users.register.phone', TYPE_BOOL);
    }

    /**
     * Отображать номер телефона указанный при регистрации в контактах профиля
     * @return bool
     */
    public static function registerPhoneContacts()
    {
        return ((bool)config::sys('users.register.phone.contacts', TYPE_BOOL)) && static::registerPhone();
    }

    /**
     * Поле ввода номера телефона
     * @param array $fieldAttr аттрибуты поля
     * @param array $options доп. параметры:
     *  'country' => ID страны по-умолчанию
     * @return string HTML
     */
    public function registerPhoneInput(array $fieldAttr = array(), array $options = array())
    {
        $fieldAttr = array_merge(array('name'=>'phone_number'), $fieldAttr);
        $countryList = Geo::i()->countriesList();
        $countrySelected = (!empty($options['country']) ? $options['country'] : Geo::i()->defaultCountry());
        if (!$countrySelected) {
            $filter = Geo::filter();
            if (!empty($filter['country'])) {
                $countrySelected = $filter['country'];
            }
        }
        if (!isset($countryList[$countrySelected])) {
            $countrySelected = key($countryList);
        }

        $aData = array(
            'attr' => &$fieldAttr,
            'options' => &$options,
            'countryList' => &$countryList,
            'countrySelected' => &$countryList[$countrySelected],
            'countrySelectedID' => $countrySelected,
        );
        return $this->viewPHP($aData, 'phone.input');
    }

    /**
     * Регистрация пользователя
     * @param array $aData данные
     * @param bool $bAuth авторизовать в случае успешной регистрации
     * @return array|bool
     *  false - ошибка регистрации
     *  array - данные о вновь созданном пользователе (user_id, password, activate_link)
     */
    public function userRegister(array $aData, $bAuth = false)
    {
        # генерируем логин на основе email-адреса
        if (isset($aData['email'])) {
            $login = mb_substr($aData['email'], 0, mb_strpos($aData['email'], '@'));
            $login = preg_replace('/[^a-z0-9\_]/ui', '', $login);
            $login = mb_strtolower(trim($login, '_ '));
            if (mb_strlen($login) >= $this->loginMinLength) {
                if (mb_strlen($login) > $this->loginMaxLength) {
                    $login = mb_substr($login, 0, $this->loginMaxLength);
                }
                $aData['login'] = $this->model->userLoginGenerate($login, true);
            } else {
                $aData['login'] = $this->model->userLoginGenerate();
            }
        }

        # генерируем пароль или используем переданный
        $sPassword = (isset($aData['password']) ? $aData['password'] : func::generator(10));

        # подготавливаем данные
        $this->cleanUserData($aData);
        $aData['password_salt'] = $this->security->generatePasswordSalt();
        $aData['password'] = $this->security->getUserPasswordMD5($sPassword, $aData['password_salt']);

        # данные необходимые для активации аккаунта
        $aActivation = $this->getActivationInfo();
        $aData['activated'] = 0;
        $aData['activate_key'] = $aActivation['key'];
        $aData['activate_expire'] = $aActivation['expire'];

        # по-умолчанию подписываем на все типы email-уведомлений
        $aData['enotify'] = $this->getEnotifyTypes(0, true);

        # создаем аккаунт
        $aData['user_id_ex'] = func::generator(6);
        $nUserID = $this->model->userCreate($aData, self::GROUPID_MEMBER);
        if (!$nUserID) {
            return false;
        }

        if ($bAuth) {
            # авторизуем
            $this->userAuth($nUserID, 'user_id', $aData['password']);
        }

        return array(
            'user_id'       => $nUserID,
            'password'      => $sPassword,
            'activate_key'  => $aActivation['key'],
            'activate_link' => $aActivation['link'],
        );
    }

    /**
     * Формируем ключ активации
     * @param array $opts дополнительные параметры ссылки активации
     * @param string $key ключ активации (если был сгенерирован ранее)
     * @return array (
     *  'key'=>ключ активации,
     *  'link'=>ссылка для активации,
     *  'expire'=>дата истечения срока действия ключа
     *  )
     */
    public function getActivationInfo(array $opts = array(), $key = '')
    {
        $aData = array();
        if (empty($key)) {
            $shortCode = (static::registerPhone() && $this->input->getpost('step', TYPE_STR) !== 'social');
            if ($shortCode) {
                # В случае регистрации через телефон генерируем короткий ключ активации
                # Кроме ситуации с регистрацией через соц. сеть
                $key = mb_strtolower(func::generator(5, false));
            } else {
                $key = md5(substr(md5(uniqid(mt_rand() . SITEHOST . '^*RD%S&()%$#', true)), 0, 10) . BFF_NOW);
            }
        }
        $aData['key'] = $opts['key'] = $key;
        $aData['link'] = static::url('activate', $opts);
        $aData['expire'] = date('Y-m-d H:i:s', strtotime('+7 days'));

        return $aData;
    }

    /**
     * ОБновляем ключ активации пользователя
     * @param integer $userID ID пользователя
     * @param string $currentKey ключ активации (если был сгенерирован ранее)
     * @return array|false
     */
    public function updateActivationKey($userID, $currentKey = '')
    {
        if (empty($userID) || $userID <0) return false;

        $activationData = $this->getActivationInfo(array(), $currentKey);
        $res = $this->model->userSave($userID, array(
            'activate_key'    => $activationData['key'],
            'activate_expire' => $activationData['expire'],
        ));
        if (!$res) {
            bff::log('Ошибка сохранения данных пользователя #'.$userID.' [users::updateActivationKey]');
            return false;
        }

        return $activationData;
    }

    /**
     * Получаем доступные варианты email-уведомлений
     * @param int $nSettings текущие активные настройки пользователя (битовое поле)
     * @param bool $bAllCheckedSettings получить битовое поле всех активированных настроек
     * @return array|int|number
     */
    public function getEnotifyTypes($nSettings = 0, $bAllCheckedSettings = false)
    {
        $aTypes = array(
            self::ENOTIFY_NEWS         => array(
                'title' => _t('users', 'Получать рассылку о новостях [site_title]', array('site_title' => config::get('title_' . LNG))),
                'a'     => 0
            ),
            self::ENOTIFY_INTERNALMAIL => array(
                'title' => _t('users', 'Получать уведомления о новых сообщениях'),
                'a'     => 0
            ),
        );
        if (BBS::commentsEnabled()) {
            $aTypes[self::ENOTIFY_BBS_COMMENTS] = array(
                'title' => _t('users', 'Получать уведомления о новых комментариях на объявления'),
                'a'     => 0
            );
        }

        if ($bAllCheckedSettings) {
            return (!empty($aTypes) ? array_sum(array_keys($aTypes)) : 0);
        }

        if (!empty($nSettings)) {
            foreach ($aTypes as $k => $v) {
                if ($nSettings & $k) {
                    $aTypes[$k]['a'] = 1;
                }
            }
        }

        return $aTypes;
    }

    /**
     * Формирование контакта пользователя в виде изображения
     * @param string|array $text текст контакта
     * @return string base64
     */
    public static function contactAsImage($text)
    {
        if (is_array($text) && sizeof($text) == 1) {
            $text = reset($text);
        }

        # Указываем шрифт
        $font = PATH_CORE . 'fonts' . DS . 'ubuntu-b.ttf';
        $fontSize = 11;
        $fontAngle = 0;

        # Определяем необходимые размера изображения
        if (is_array($text)) {
            $textMulti = join("\n", $text);
            $textDimm = imagettfbbox($fontSize, $fontAngle, $font, $textMulti);
        } else {
            $textDimm = imagettfbbox($fontSize, $fontAngle, $font, $text);
        }
        if ($textDimm === false) {
            return '';
        }
        $width = ($textDimm[4] - $textDimm[6]) + 2;
        $height = ($textDimm[1] - $textDimm[7]) + 2;

        # Создаем холст
        $image = imagecreatetruecolor($width, $height);

        # Формируем прозрачный фон
        imagealphablending($image, false);
        $transparentColor = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparentColor);
        imagesavealpha($image, true);

        # Пишем текст
        $textColor = imagecolorallocate($image, 0x33, 0x33, 0x33); # цвет текста
        if (is_array($text)) {
            $i = 0;
            foreach ($text as $v) {
                $y = ($i++ * $fontSize) + (5 * $i) + 8;
                imagettftext($image, $fontSize, $fontAngle, 0, $y, $textColor, $font, $v);
            }
        } else {
            imagettftext($image, $fontSize, $fontAngle, 0, $height - 2, $textColor, $font, $text);
        }

        # Формируем base64 версию изображения
        ob_start();
        imagepng($image);
        imagedestroy($image);
        $data = ob_get_clean();
        $data = 'data:image/png;base64,' . base64_encode($data);

        return $data;
    }

    /**
     * Валидация номеров телефонов
     * @param array $aPhones номера телефонов
     * @param int $nLimit лимит
     * @return array
     */
    public static function validatePhones(array $aPhones = array(), $nLimit = 0)
    {
        $aResult = array();
        foreach ($aPhones as $v) {
            $v = preg_replace('/[^\s\+\-0-9]/', '', $v);
            $v = preg_replace('/\s+/', ' ', $v);
            $v = trim($v, '- ');
            if (strlen($v) > 4) {
                $v = mb_substr($v, 0, 20);
                $v = trim($v, '- ');
                $v = (strpos($v, '+') === 0 ? '+' : '') . str_replace('+', '', $v);
                $phone = array('v' => $v);
                $phone['m'] = mb_substr(trim($v, ' -+'), 0, 2) . 'x xxx xxxx';
                $aResult[] = $phone;
            }
        }
        if ($nLimit > 0 && sizeof($aResult) > $nLimit) {
            $aResult = array_slice($aResult, 0, $nLimit);
        }

        return $aResult;
    }

    /**
     * Формирование ключа для авторизации от имени пользователя (из админ. панели)
     * @param integer $userID ID пользователя
     * @param string $userLastLogin дата последней авторизации
     * @param string $userEmail E-mail пользователя
     * @param boolean $onlyHash вернуть только hash
     * @return string
     */
    function adminAuthURL($userID, $userLastLogin, $userEmail, $onlyHash = false)
    {
        $hash = $this->security->getRememberMePasswordMD5($userID.md5($userLastLogin).config::sys('site.title').$userEmail);
        if ($onlyHash) {
            return $hash;
        }

        return static::urlBase().'/user/login_admin?hash='.$hash.'&uid='.$userID;
    }

}