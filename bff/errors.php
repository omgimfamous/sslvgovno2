<?php

/**
 * Класс обработки ошибок
 * @version 0.46
 * @modified 28.sep.2015
 *
 * Ключи необходимых шаблонов в TPL_PATH:
 *  - error.layout      - Ошибки: Layout шаблон
 *  - error.common      - Ошибки: cтандартный шаблон ошибок
 *  - error.404         - Ошибки: 404 ошибка
 *  - error.exception   - Ошибки: исключения
 *  - message.success   - Сообщения: "Успешно"
 *  - message.forbidden - Сообщения: "В доступе отказано"
 */
class Errors extends bff\Singleton
{
    /** @var array список обработанных ошибок */
    protected $errors = array();
    /** @var array список полей(ключей), с которыми связаны ошибки */
    protected $fields = array();
    /** @var bool сворачивать блок ошибки автоматически по таймауту */
    protected $isAutohide = true;
    /** @var array необходимые шаблоны в TPL_PATH */
    protected $templates = array(
        # Ошибки:
        'error.layout'      => 'error', # Layout шаблон
        'error.common'      => 'error.common', # Стандартный шаблон ошибок
        'error.404'         => 'error.404', # 404 ошибка
        'error.exception'   => 'error.exception', # Исключения
        # Сообщения:
        'message.success'   => 'message.success', # сообщение "Успешно"
        'message.forbidden' => 'message.forbidden', # сообщение "В доступе отказано"
    );
    /** @var bool подавлять warning'и */
    protected $suppressWarnings = false;

    # коды ошибок
    const SUCCESS       = 1;
    const IMPOSSIBLE    = 4;
    const UNKNOWNRECORD = 402;
    const ACCESSDENIED  = 403;
    const RELOAD_PAGE   = 114;
    const DEMO_LIMITED  = 117;

    # код ошибки загрузки файла
    const FILE_UPLOAD_ERROR   = 1; # Ошибка загрузки файла
    const FILE_WRONG_SIZE     = 2; # Некорректный размер файла
    const FILE_MAX_SIZE       = 3; # Файл превышает масимально допустимый размер
    const FILE_DISK_QUOTA     = 4; # Ошибка загрузки файла (превышена квота на диске)
    const FILE_WRONG_TYPE     = 5; # Запрещенный тип файла
    const FILE_WRONG_NAME     = 6; # Некорректное имя файла
    const FILE_ALREADY_EXISTS = 7; # Файл с таким названием уже был загружен ранее
    const FILE_MAX_DIMENTION  = 8; # Изображение слишком большое по ширине/высоте

    /**
     * @return Errors
     */
    public static function i()
    {
        return parent::i();
    }

    protected function __construct()
    {
        set_error_handler(array($this, 'triggerErrorHandler'), E_ALL);
        //register_shutdown_function( array($this, 'triggerShutdownHandler') );
    }

    /**
     * Переопределение шаблонов
     * @param array $templates список шаблонов: array(ключ шаблона => название шаблона в директории TPL_PATH, ...)
     */
    public function setTemplates(array $templates = array())
    {
        foreach ($templates as $k => $v) {
            if (!empty($v) && isset($this->templates[$k])) {
                $this->templates[$k] = $v;
            }
        }
    }

    /**
     * Сохраняем сообщение об ошибке
     * @param string|integer $error текст ошибки или ключ(например Errors::SUCCESS)
     * @param mixed $system boolean::true - системная ошибка, string::'key' - не системная, ключ ошибки
     * @param mixed $key ключ ошибки или имя input-поля
     * @return Errors объект
     */
    public function set($error, $system = false, $key = null)
    {
        if (is_int($error)) {
            $message = $this->getSystemMessage($error);
        } else {
            $message = $error;
        }

        $errorData = array('sys' => ($system === true), 'errno' => $error, 'msg' => $message);
        if (!isset($key) && is_string($system)) {
            $key = $system;
        }
        if (isset($key)) {
            $this->errors[$key] = $errorData;
            $this->field($key);
        } else {
            $this->errors[] = $errorData;
        }

        return $this;
    }

    /**
     * Получаем сообщения об ошибках
     * @param bool $onlyMessages только текст
     * @param bool $excludeSystem исключая системные
     */
    public function get($onlyMessages = true, $excludeSystem = true)
    {
        if (BFF_DEBUG || FORDEV) {
            $excludeSystem = false;
        }

        if ($onlyMessages) {
            if (empty($this->errors)) {
                return array();
            }
            $res = array();
            foreach ($this->errors as $k => $v) {
                if ($excludeSystem && $v['sys']) {
                    continue;
                }
                $res[$k] = $v['msg'];
            }

            return $res;
        } else {
            if ($excludeSystem) {
                $res = array();
                foreach ($this->errors as $k => $v) {
                    if ($v['sys']) {
                        continue;
                    }
                    $res[$k] = $v;
                }

                return $res;
            } else {
                return $this->errors;
            }
        }
    }

    /**
     * Получаем текст последней ошибки
     * @return string|boolean
     */
    public function getLast()
    {
        if (empty($this->errors)) {
            return false;
        }
        $return = end($this->errors);
        reset($this->errors);

        return (isset($return['msg']) ? $return['msg'] : false);
    }

    /**
     * Помечаем список ключ поля(нескольких полей), с которым связаны добавленные ошибки
     * @param string|array $key ключ поля(нескольких полей)
     */
    public function field($key)
    {
        if (is_string($key)) {
            $this->fields[] = $key;
        } else {
            if (is_array($key)) {
                foreach ($key as $k) {
                    $this->fields[] = $k;
                }
            }
        }
    }

    /**
     * Помечаем список ключей полей, с которым связаны добавленные ошибки
     * @return array
     */
    public function fields()
    {
        return array_unique($this->fields);
    }

    /**
     * Помечаем невозможность выполнения операции
     * @return Errors
     */
    public function impossible()
    {
        return $this->set(self::IMPOSSIBLE);
    }

    /**
     * Помечаем ошибку доступа
     * @return Errors
     */
    public function accessDenied()
    {
        return $this->set(self::ACCESSDENIED);
    }

    /**
     * Помечаем ошибку (ID редактируемой записи некорректный)
     * @return Errors
     */
    public function unknownRecord()
    {
        return $this->set(self::UNKNOWNRECORD);
    }

    /**
     * Помечаем ошибку (требуется перезагрузка страницы)
     * @return Errors
     */
    public function reloadPage()
    {
        return $this->set(self::RELOAD_PAGE);
    }

    /**
     * Помечаем ошибку (действуют демо ограничения)
     * @return Errors
     */
    public function demoLimited()
    {
        return $this->set(self::DEMO_LIMITED);
    }

    /**
     * Помечаем успешность выполнения действия
     * @return Errors
     */
    public function success()
    {
        $this->set(self::SUCCESS);
        $_GET['errno'] = $_POST['errno'] = self::SUCCESS;

        return $this;
    }

    /**
     * Получаем успешность выполнения действия
     * @return bool
     */
    public function isSuccess()
    {
        $errorNumber = bff::input()->getpost('errno', TYPE_UINT);
        $success = ($errorNumber == self::SUCCESS || (!$errorNumber && $this->no()));
        if ($errorNumber > 0 && $this->no()) {
            $this->set($errorNumber);
        }

        return $success;
    }

    /**
     * Получаем информацию о наличии ошибок
     * @return bool true - нет; false - есть
     */
    public function no()
    {
        return (sizeof($this->errors) == 0);
    }

    /**
     * Обнуляем информацию о существующих ошибках
     */
    public function clear()
    {
        $this->errors = array();
    }

    /**
     * Помечаем необходимость автоматического сворачивания блока ошибок
     * @param bool|null $hide true/false - помечаем требуемое состояние, NULL - получаем текущее
     * @return bool
     */
    public function autohide($hide = null)
    {
        if (is_null($hide)) {
            return $this->isAutohide;
        } else {
            return ($this->isAutohide = $hide);
        }
    }

    /**
     * Отображаем 404 HTTP ошибку
     */
    public function error404()
    {
        SEO::i()->robotsIndex(false);
        $this->errorHttp(404);
    }

    /**
     * Отображаем HTTP ошибку
     * @param int $errorCode код http ошибки, например: 404
     * @param mixed $template название PHP шаблона или FALSE ('error.common')
     * @param mixed $templateDir путь к шаблону или FALSE (TPL_PATH)
     */
    public function errorHttp($errorCode, $template = false, $templateDir = false)
    {
        $errorCode = intval($errorCode);
        $serverProtocol = Request::getSERVER('SERVER_PROTOCOL', 'HTTP/1.1');
        $data = array('errno' => $errorCode, 'title' => _t('errors', 'Внутренняя ошибка сервера'), 'message' => '');
        switch ($errorCode) {
            case 401:
            {
                header('WWW-Authenticate: Basic realm="' . Request::host(SITEHOST) . '"');
                header($serverProtocol . ' 401 Unauthorized');
                $data['title'] = _t('errors', 'Доступ запрещен');
                $data['message'] = _t('errors', 'Вы должны ввести корректный логин и пароль для получения доступа к ресурсу.');
            }
            break;
            case 403:
            {
                # пользователь не прошел аутентификацию, запрет на доступ (Forbidden).
                header($serverProtocol . ' 403 Forbidden');
                $data['title'] = _t('errors', 'Доступ запрещен');
                $data['message'] = _t('errors', 'Доступ к указанной странице запрещен');
            }
            break;
            case 404:
            {
                header($serverProtocol . ' 404 Not Found');
                $template = $this->templates['error.404'];
                $data['title'] = _t('errors', 'Страница не найдена!');
                $data['message'] = _t('errors', 'Страницы, на которую вы попытались войти не существует.');
            }
            break;
            default:
            {
                if (!empty($errorCode)) {
                    header($serverProtocol . ' ' . $errorCode);
                    $data['title'] = _t('errors', 'Внутренняя ошибка сервера');
                    $data['message'] = _t('errors', 'Произошла внутренняя ошибка сервера (' . $errorCode . ')');
                } else {
                    $data['title'] = _t('errors', 'Внутренняя ошибка сервера');
                    $data['message'] = _t('errors', 'Произошла внутренняя ошибка сервера');
                }
            }
            break;
        }

        echo $this->viewError($data, $template, $templateDir);
        exit;
    }

    /**
     * Выводим ошибку
     * @param array $data данные об ошибке: errno, title, message
     * @param mixed $template название PHP шаблона или FALSE ('error.common')
     * @param mixed $templateDir путь к шаблону или FALSE (TPL_PATH)
     * @return string HTML
     */
    public function viewError(array $data = array(), $template = false, $templateDir = false)
    {
        $data['centerblock'] = View::renderTemplate($data, (!empty($template) ? $template : $this->templates['error.common']), $templateDir);

        return View::renderLayout($data, $this->templates['error.layout'], $templateDir);
    }

    /**
     * Отображаем уведомление "Успешно..." (frontend)
     * @param string $title заголовок сообщения
     * @param string $message текст сообщения
     * @return string HTML
     */
    public function messageSuccess($title, $message)
    {
        return $this->viewMessage($title, $message, false, $this->templates['message.success']);
    }

    /**
     * Отображаем уведомление об "Ошибке..." (frontend)
     * @param string $title заголовок сообщения
     * @param string $message текст сообщения
     * @param bool $auth требуется авторизация
     * @return string HTML
     */
    public function messageForbidden($title, $message, $auth = false)
    {
        return $this->viewMessage($title, $message, $auth, $this->templates['message.forbidden']);
    }

    /**
     * Отображаем сообщение (frontend)
     * @param string $title заголовок сообщения
     * @param string $message текст сообщения
     * @param bool $auth требуется авторизация
     * @param mixed $template название PHP шаблона или FALSE ('message.forbidden')
     * @param mixed $templateDir путь к шаблону или FALSE (TPL_PATH)
     * @return string HTML
     */
    public function viewMessage($title, $message = '', $auth = false, $template = false, $templateDir = false)
    {
        $data = array('title' => $title, 'message' => $message, 'auth' => $auth);

        return View::renderTemplate($data, (!empty($template) ? $template : $this->templates['message.forbidden']), $templateDir);
    }

    /**
     * Перехватываем trigger_error и пишем в лог ошибок (/files/errors.log)
     * @param integer $errorCode код ошибки
     * @param string $message текст ошибки
     * @param string $errorFile файл, в котором произошла ошибка
     * @param string $errorLine строка файла, на которой произошла ошибка
     */
    public function triggerErrorHandler($errorCode, $message, $errorFile, $errorLine)
    {
        if ($errorCode == E_WARNING && $this->suppressWarnings) {
            return;
        }
        if (in_array($errorCode, array(
                E_USER_ERROR,
                E_USER_WARNING,
                E_USER_NOTICE,
                E_STRICT,
                E_ERROR,
                E_NOTICE,
                E_WARNING
            )
        )
        ) {
            $this->set($message . '<br />' . $errorFile . ' [' . $errorLine . ']', true);
        }
        bff::log("$message > $errorFile [$errorLine]");
    }

    /**
     * Перехватываем shutdown_function
     */
    public function triggerShutdownHandler()
    {
        $lastError = error_get_last();
        if ($lastError['type'] === E_ERROR) {
            $this->triggerErrorHandler(E_ERROR, $lastError['message'], $lastError['file'], $lastError['line']);
        }
    }

    /**
     * Подавлять ошибки
     * @param boolean $suppress
     */
    public function suppressWarnings($suppress)
    {
        $this->suppressWarnings = $suppress;
    }

    /**
     * Сохраняем сообщение об ошибке загрузки
     * @param integer $uploadErrorCode код ошибки загрузки
     * @param array $params доп. параметры ошибки
     * @param mixed $system boolean::true - системная ошибка, string::'key' - не системная, ключ ошибки
     * @param mixed $key ключ ошибки или имя input-поля
     * @return Errors
     */
    public function setUploadError($uploadErrorCode, array $params = array(), $system = false, $key = null)
    {
        return $this->set($this->getUploadErrorMessage($uploadErrorCode, $params), $system, $key);
    }

    /**
     * Получаем текст ошибки загрузки файла по коду
     * @param integer $uploadErrorCode код ошибки загрузки
     * @param array $params доп. параметры ошибки
     * @return string текст ошибки
     */
    public function getUploadErrorMessage($uploadErrorCode, array $params = array())
    {
        switch ($uploadErrorCode) {
            case self::FILE_UPLOAD_ERROR:
                $message = _t('upload', 'Ошибка загрузки файла', $params);
                break;
            case self::FILE_WRONG_SIZE:
                $message = _t('upload', 'Некорректный размер файла', $params);
                break;
            case self::FILE_MAX_SIZE:
                $message = _t('upload', 'Файл превышает масимально допустимый размер', $params);
                break;
            case self::FILE_DISK_QUOTA:
                $message = _t('upload', 'Ошибка загрузки файла, обратитесь к администратору', $params);
                break;
            case self::FILE_WRONG_TYPE:
                $message = _t('upload', 'Запрещенный тип файла', $params);
                break;
            case self::FILE_WRONG_NAME:
                $message = _t('upload', 'Некорректное имя файла', $params);
                break;
            case self::FILE_ALREADY_EXISTS:
                $message = _t('upload', 'Файл с таким названием уже был загружен ранее', $params);
                break;
            case self::FILE_MAX_DIMENTION:
                $message = _t('upload', 'Изображение слишком большое по ширине/высоте', $params);
                break;
            default:
                $message = _t('upload', 'Ошибка загрузки файла');
                break;
        }
        return $message;
    }

    /**
     * Получаем текст ошибки по коду ошибки
     * @param integer $errorCode код ошибки
     * @return string текст ошибки
     */
    public function getSystemMessage($errorCode)
    {
        switch ($errorCode) {
            case self::SUCCESS: # Operation is successfull
                return _t('system', 'Операция выполнена успешно');
                break;
            case self::ACCESSDENIED: # Access denied
                return _t('system', 'В доступе отказано');
                break;
            case self::IMPOSSIBLE: # Unable to complete operation
                return _t('system', 'Невозможно выполнить операцию');
                break;
            case self::UNKNOWNRECORD: # Unable to complete operation
                return _t('system', 'Невозможно выполнить операцию');
                break;
            case self::RELOAD_PAGE: # Reload page and retry
                return _t('system', 'Обновите страницу и повторите попытку');
                break;
            case self::DEMO_LIMITED: # This operation is not allowed in demo mode
                return _t('system', 'Данная операция недоступна в режиме просмотра демо-версии');
                break;
            default: # Unknown error
                return _t('system', 'Неизвестная ошибка');
                break;
        }
    }
}