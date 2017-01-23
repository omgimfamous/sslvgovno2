<?php namespace bff\files;

/**
 * Компонент управляющий загрузкой файлов вложений
 * @version 0.36
 * @modified 25.may.2013
 */

class Attachment
{
    /** @var int максимально допустимый объем файла (в байтах) */
    protected $maxSize = 0;
    /** @var string путь к директории хранения файлов */
    protected $path = '';
    /** @var boolean выполнять проверку доступного места на диске */
    protected $checkDiskFreeSpace = true;
    /** @var boolean минимально допустимый размер свободного места на диске (в байтах) */
    protected $minimalDiskFreeSpace = 524288000;
    /** @var boolean сообщать об ошибках */
    protected $assignErrors = false;
    /** @var boolean возвращать данные о загруженном файле в виде строки с разделителем ";" */
    protected $filedataAsString = true;
    protected $filedataSeparator = ';';

    /**
     * @var array список разрешенных расширений файлов
     * @Example:
     * 'jpg','jpeg','gif','png','bmp','tiff','ico','doc','docx','xls','rtf',
     * 'pdf','djvu','zip','gzip','gz','7z','rar','txt','sql',
     */
    protected $extensionsAllowed = array();
    /** @var array список запрещенных расширений файлов */
    protected $extensionsForbidden = array(
        'php',
        'php2',
        'php3',
        'php4',
        'php5',
        'php6',
        'phtml',
        'pwml',
        'inc',
        'asp',
        'aspx',
        'ascx',
        'jsp',
        'cfm',
        'cfc',
        'pl',
        'py',
        'rb',
        'bat',
        'exe',
        'com',
        'cmd',
        'dll',
        'so',
        'vbs',
        'vbe',
        'js',
        'jse',
        'reg',
        'cgi',
        'wsf',
        'wsh',
    );

    /**
     * Инициализация
     * @param string $path путь к директории хранения файлов
     * @param integer $maxSize максимально допустимый размер вложения (в байтах)
     */
    public function __construct($path, $maxSize = 0)
    {
        $this->path = $path;
        $this->maxSize = $maxSize;
    }

    /**
     * Устанавливаем максимально допустимый размер вложения (в байтах)
     * @param integer $maxSize 0 - без ограничения
     */
    public function setMaxSize($maxSize = 0)
    {
        $this->maxSize = $maxSize;
    }

    /**
     * Получаем максимально допустимый размер вложения (в байтах)
     * @return integer
     */
    public function getMaxSize()
    {
        return $this->maxSize;
    }

    /**
     * Проверка допустимости расширения файла
     * @param string $extension расширение файла
     * @return bool
     */
    public function isAllowedExtension($extension = '')
    {
        # проверяем по списку разрешенных
        if (!empty($this->extensionsAllowed)) {
            return in_array($extension, $this->extensionsAllowed);
        } # проверяем по списку запрещенных
        elseif (!empty($this->extensionsForbidden)) {
            return !in_array($extension, $this->extensionsForbidden);
        }

        return true;
    }

    /**
     * Устанавливаем список разрешенных расширений файлов
     * @param array $extensions
     */
    public function setAllowedExtensions(array $extensions = array())
    {
        $this->extensionsAllowed = $extensions;
    }

    /**
     * Устанавливаем список запрещенных расширений файлов
     * @param array $extensions
     */
    public function setForbiddenExtensions(array $extensions = array())
    {
        $this->extensionsForbidden = $extensions;
    }

    /**
     * Выполнять проверку свободного места на диске
     * @param boolean $check
     */
    public function setCheckFreeDiskSpace($check)
    {
        $this->checkDiskFreeSpace = $check;
    }

    /**
     * Сообщать об ошибках
     * @param boolean $assignErrors
     */
    public function setAssignErrors($assignErrors)
    {
        $this->assignErrors = $assignErrors;
    }

    /**
     * Формировать результат загрузки файла в виде строки
     * @param boolean $filedataAsString
     */
    public function setFiledataAsString($filedataAsString)
    {
        $this->filedataAsString = $filedataAsString;
    }

    /**
     * Загрузка файла стандартным методом
     * @param string $inputName имя file-поля
     * @param integer $limit ограничение максимального кол-ва одновременно загружаемых файлов
     * @return mixed
     */
    public function uploadFILES($inputName, $limit = 1)
    {
        # Загружались ли файлы
        if (empty($_FILES)) {
            return false;
        }

        # Достаточно ли свободного места на диске
        if ($this->checkDiskFreeSpace && $diskFreeSpace = @disk_free_space($this->path)) {
            if ($diskFreeSpace <= $this->minimalDiskFreeSpace) {
                trigger_error('attach_quota_reached');

                return false;
            }
        }

        $attachments = array();
        $limit = intval($limit);
        if ($limit <= 0) $limit = 1;

        $i = 1;
        foreach (array_reverse($_FILES) as $fileKey => $FILE) {
            # Файл не был загружен
            if (strpos($fileKey, $inputName) === false || $FILE['error'] == UPLOAD_ERR_NO_FILE)
                continue;

            $attachments[$i] = array(
                'error'     => 0,
                'filesize'  => @filesize($FILE['tmp_name']),
                'rfilename' => $FILE['name'],
                'extension' => mb_strtolower(pathinfo($FILE['name'], PATHINFO_EXTENSION)),
            );
            if ($attachments[$i] == 'jpeg') {
                $attachments[$i] = 'jpg';
            }

            if (!$attachments[$i]['filesize'])
                $attachments[$i]['filesize'] = $FILE['size'];

            # Не указана ли ошибка загрузки
            if ($FILE['error'] != UPLOAD_ERR_OK) {
                switch ($FILE['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                    {
                        # 1: The uploaded file exceeds the upload_max_filesize directive in php.ini.
                        # 2: The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.
                        $attachments[$i]['error'] = \Errors::FILE_MAX_SIZE;
                    }
                    break;
                    default:
                    {
                        # 3: The uploaded file was only partially uploaded.
                        # 4: No file was uploaded.
                        # 6: Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3.
                        # 7: Failed to write file to disk. Introduced in PHP 5.1.0.
                        # 8: A PHP extension stopped the file upload.
                        $attachments[$i]['error'] = \Errors::FILE_UPLOAD_ERROR;
                    }
                    break;
                }
                continue;
            }

            # Проверка имени файла
            if (preg_match("/[\\/:;*?\"<>]/i", $FILE['name'])) {
                $attachments[$i]['error'] = \Errors::FILE_WRONG_NAME;
                continue;
            }

            # Проверка размера файла
            if ($attachments[$i]['filesize'] <= 0) {
                $attachments[$i]['error'] = \Errors::FILE_WRONG_SIZE;
                continue;
            }
            if (!empty($this->maxSize) && $attachments[$i]['filesize'] > $this->maxSize) {
                $attachments[$i]['error'] = \Errors::FILE_MAX_SIZE;
                continue;
            }

            # Проверка расширения файла
            if (!$this->isAllowedExtension($attachments[$i]['extension'])) {
                $attachments[$i]['error'] = \Errors::FILE_WRONG_TYPE;
                continue;
            }

            # Проверка свободного места на диске
            if ($this->checkDiskFreeSpace && $diskFreeSpace <= $attachments[$i]['filesize']) {
                $attachments[$i]['error'] = \Errors::FILE_DISK_QUOTA;
                continue;
            }

            # Формируем путь к файлу
            $filename = $this->generateFilename($attachments[$i]['extension']);
            $filepath = $this->path . $filename;

            # Загрузка
            if (!move_uploaded_file($FILE['tmp_name'], $filepath)) {
                $attachments[$i]['error'] = \Errors::FILE_UPLOAD_ERROR;
                continue;
            }

            $attachments[$i]['filename'] = $filename;

            if (++$i > $limit)
                break;
        }

        if ($limit == 1) {
            $attach = (!empty($attachments) ? reset($attachments) : array());

            return $this->prepareFiledata($attach);
        }

        return $attachments;
    }

    /**
     * Загрузка файла при помощи QQ-загрузчика
     * @return mixed
     */
    public function uploadQQ()
    {
        require_once PATH_CORE . 'external' . DS . 'qquploader.php';

        $uploader = new \qqFileUploader($this->extensionsAllowed, $this->maxSize, $this->assignErrors);
        $extension = $uploader->getFilenameExtension();

        $result = array(
            'error'     => 0,
            'filename'  => $this->generateFilename($extension),
            'filesize'  => $uploader->getFilesize(),
            'rfilename' => $uploader->getFilename(),
            'extension' => $extension,
        );

        do {
            # Проверка имени файла
            if (preg_match("/[\\/:;*?\"<>]/i", $result['rfilename'])) {
                $result['error'] = \Errors::FILE_WRONG_NAME;
                break;
            }

            # Проверка расширения файла
            if (!$this->isAllowedExtension($extension)) {
                $result['error'] = \Errors::FILE_WRONG_TYPE;
                break;
            }

            # Загрузка
            if ($uploader->upload($this->path . $result['filename']) !== true) {
                $result['error'] = \Errors::i()->getLast();
                break;
            }

        } while (false);

        return $this->prepareFiledata($result);
    }

    /**
     * Формирование данных о загруженном файле
     * @param mixed $data
     * @return mixed
     */
    protected function prepareFiledata($data)
    {
        if (empty($data)) {
            return false;
        }
        if (!empty($data['error'])) {
            if ($this->assignErrors) {
                \Errors::i()->setUploadError($data['error']);
            }

            return false;
        }
        if ($this->filedataAsString) {
            $data = join($this->filedataSeparator, array(
                    $data['filename'],
                    $data['filesize'],
                    $data['extension'],
                    strip_tags($data['rfilename'])
                )
            );
        }

        return $data;
    }

    /**
     * Формирование имени для загружаемого файла
     * @param string $extension расширение файла
     * @return string
     */
    protected function generateFilename($extension)
    {
        return \func::generator(10, true) . '.' . mb_strtolower($extension);
    }

    /**
     * Получаем общий объем файлов в директории {$this->path}
     * @return integer
     */
    public function getDirSize()
    {
        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->path)) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

}