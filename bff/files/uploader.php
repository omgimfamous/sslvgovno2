<?php

/**
 * Класс загрузки файлов
 * @version 1.524
 * @modified 18.dec.2013
 */
class CUploader
{
    protected $fieldname;
    protected $filename = '';
    protected $filename_saved = '';

    # Типы ошибок при загрузке:
    const errNoError = 0; # Нет ошибок
    const errUploadError = 1; # Ошибка при загрузке файла
    const errWrongSize = 2; # Некорректный размер файла
    const errMaxSize = 3; # Превышен максимально допустимый размер файла
    const errDiskQuota = 4; # Превышена квота допустимого места на диске
    const errWrongType = 5; # Запрещенный тип файла
    const errWrongName = 6; # Некорректное имя файла
    const errAlreadyExists = 7; # Файл с указанным именем уже существует
    const errMaxDimention = 8; # Превышен максимально допустимый масштаб файла изображения

    protected $errors = null;

    /**
     * Конструктор
     * @param string $sFieldname ключ в массиве FILES
     * @param boolean $isCompulsory сообщать ли об ошибке, в случае неудачно загруженного файла
     * @return boolean
     */
    public function __construct($sFieldname, $isCompulsory = false)
    {
        $this->fieldname = $sFieldname;

        if (!isset($_FILES[$sFieldname]) || empty($_FILES[$sFieldname]['name'])) {
            if ($isCompulsory) {
                return $this->error('no_file');
            }
        }

        if ($_FILES[$sFieldname]['error'] != UPLOAD_ERR_OK) {
            switch ($_FILES[$sFieldname]['error']) {
                case UPLOAD_ERR_INI_SIZE: # file exceeds the upload_max_filesize
                case UPLOAD_ERR_FORM_SIZE: # file exceeds the MAX_FILE_SIZE
                {
                    //$this->error('size_max', $maxSize);
                }
                break;
            }
        }

        if (!is_uploaded_file($_FILES[$sFieldname]['tmp_name'])) {
            if ($isCompulsory) {
                return $this->error('no_file');
            }
        } else {
            if (isset($_FILES[$sFieldname])) {
                $this->filename = $_FILES[$sFieldname]['name'];
            }
        }
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function getFilenameUploaded()
    {
        return $_FILES[$this->fieldname]['tmp_name'];
    }

    public function getFilenameSaved()
    {
        return $this->filename_saved;
    }

    public function checkIsIMG()
    {
        if ($this->isError()) {
            return false;
        }

        if (strpos($_FILES[$this->fieldname]['type'], 'image/') !== false) {
            return true;
        }

        return $this->error('upload_img');
    }

    public function checkSize($maxSize = null)
    {
        if ($this->isError()) {
            return false;
        }

        if ($_FILES[$this->fieldname]['size'] != 0) {
            if (isset($maxSize) && $_FILES[$this->fieldname]['size'] > $maxSize) {
                return $this->error('size_max', $maxSize);
            }
        } else {
            return $this->error('size_0');
        }

        return true;
    }

    /**
     * Сохраняем загруженный файл по указанному пути
     * @param string $path путь для сохранения
     * @param string $sFilenamePrefix префикс добавляемый к имени сохраняемого файла, либо полное имя файла в случае если $bAddFilename = false
     * @param boolean $bAddFilename использовать ли оригинальное имя файла
     * @param integer $chmod
     * @return boolean
     */
    public function save($path, $sFilenamePrefix = '', $bAddFilename = true, $chmod = 0777)
    {
        if ($this->isError()) {
            return false;
        }

        if (!is_dir($path)) {
            return $this->error('path should be a folder:' . $path);
        }

        $path = rtrim($path, '\\/');
        $filename = $sFilenamePrefix . ($bAddFilename ? $this->filename : '');

        $res = $this->_save($path . '/' . $filename, $chmod);

        $this->filename_saved = ($res === true ? $filename : false);

        return $res;
    }

    /**
     * Сохраняем загруженный файл по указанному пути
     * @param string $sFilePath путь для сохранения файла
     * @param integer $chmod
     * @return boolean
     */
    protected function _save($sFilePath, $chmod = 0777)
    {
        if ($this->isError()) {
            return false;
        }

        if (!move_uploaded_file($this->getFilenameUploaded(), $sFilePath)) {
            return $this->error('cant_write_file', $sFilePath);
        } else {
            if ($chmod !== false) {
                @chmod($sFilePath, $chmod);
            }

            return true;
        }
    }

    public function error($errno, $extra = '')
    {
        $errorMsg = '';
        switch ($errno) {
            case 'no_file':
                $errorMsg = 'Please upload file';
                break;
            case 'upload_img':
                $errorMsg = 'Please upload image file';
                break;
            case 'size_0':
                $errorMsg = 'The size of uploaded file should be more then "0"';
                break;
            case 'size_max':
                $errorMsg = 'The size of uploaded file should be less then: ' . $extra;
                break;
            case 'cant_write_file':
                $errorMsg = 'Cant write file: ' . $extra;
                break;
            case 'no_thumbnail.class':
                $errorMsg = 'Thumbnail class is required';
                break;
            case 'upload_audio_or_video':
                $errorMsg = 'Please upload audio or video files';
                break;
        }

        $this->errors[] = $errorMsg;
        Errors::i()->set($errorMsg);

        return false;
    }

    public function isSuccessfull()
    {
        return !$this->isError();
    }

    public function isError()
    {
        return (!empty($this->errors));
    }

    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Начало загрузки файла через SWFUpload
     * проверка размера файла, ...
     * @param boolean $bReturnErrors возвращать ошибки
     * @param array $extension_whitelist список разрешенных разрешений файлов
     * @param array $only_images список разрешенных типов изображений
     */
    public static function swfuploadStart($bReturnErrors = true, $extension_whitelist = array(
        'jpg',
        'jpeg',
        'gif',
        'png'
    ), $only_images = array(IMAGETYPE_JPEG, IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_PNG)
    ) {
        # post_max_size
        $POST_MAX_SIZE = ini_get('post_max_size');
        $unit = mb_strtoupper(substr($POST_MAX_SIZE, -1));
        $multiplier = ($unit == 'M' ? 1048576 : ($unit == 'K' ? 1024 : ($unit == 'G' ? 1073741824 : 1)));

        if ((int)$_SERVER['CONTENT_LENGTH'] > $multiplier * (int)$POST_MAX_SIZE && $POST_MAX_SIZE) {
            return self::swfuploadError((FORDEV ? _t('swfupload', 'Превышен максимально допустимый размер POST запроса([post_max]).', array('post_max' => $POST_MAX_SIZE)) :
                    _t('swfupload', 'Ошибка загрузки файла')), $bReturnErrors
            );
        }

        $upload_name = 'Filedata';
        $max_file_size_in_bytes = 2147483647; # 2GB в байтах
        $valid_chars_regex = '.A-Z0-9_ !@#$%^&()+={}\[\]\',~`-'; # Допустимые символы в имени файла (в формате регулярного выражения)
        $MAX_FILENAME_LENGTH = 260;

        $uploadErrors = array(
            UPLOAD_ERR_INI_SIZE   => (FORDEV ? _t('swfupload', 'Размер файла больше разрешенного директивой upload_max_filesize в php.ini') :
                    _t('swfupload', 'Размер файла превышает допустимый')),
            UPLOAD_ERR_FORM_SIZE  => (FORDEV ? _t('swfupload', 'Размер файла превышает указанное значение в MAX_FILE_SIZE') :
                    _t('swfupload', 'Размер файла превышает допустимый')),
            UPLOAD_ERR_PARTIAL    => _t('swfupload', 'Файл был загружен только частично'),
            UPLOAD_ERR_NO_FILE    => _t('swfupload', 'Не был выбран файл для загрузки'),
            UPLOAD_ERR_NO_TMP_DIR => (FORDEV ? _t('swfupload', 'Не найдена папка для временных файлов') :
                    _t('swfupload', 'Ошибка загрузки файла')),
            UPLOAD_ERR_CANT_WRITE => (FORDEV ? _t('swfupload', 'Ошибка записи файла на диск') :
                    _t('swfupload', 'Ошибка загрузки файла')),
            UPLOAD_ERR_EXTENSION  => (FORDEV ? _t('swfupload', 'Загрузка файла была остановлена одним из приложений(extensions) php') :
                    _t('swfupload', 'Ошибка загрузки файла')),
        );

        # Validate the upload
        if (!isset($_FILES[$upload_name])) {
            return self::swfuploadError(
                (FORDEV ? _t('swfupload', 'Информация о загрузке [file] не указана', array('file' => $upload_name)) :
                    _t('swfupload', 'Ошибка загрузки файла')), $bReturnErrors
            );
        } else {
            if (isset($_FILES[$upload_name]["error"]) && $_FILES[$upload_name]["error"] != 0) {
                return self::swfuploadError($uploadErrors[$_FILES[$upload_name]["error"]], $bReturnErrors);
            } else {
                if (!isset($_FILES[$upload_name]["tmp_name"]) || !@is_uploaded_file($_FILES[$upload_name]["tmp_name"])) {
                    return self::swfuploadError(
                        (FORDEV ? _t('swfupload', 'Проверка загруженного файла на is_uploaded_file не прошла.') :
                            _t('swfupload', 'Ошибка загрузки файла')), $bReturnErrors
                    );
                } else {
                    if (!isset($_FILES[$upload_name]['name'])) {
                        return self::swfuploadError(
                            (FORDEV ? _t('swfupload', 'Имя файла не было указано.') :
                                _t('swfupload', 'Ошибка загрузки файла')), $bReturnErrors
                        );
                    }
                }
            }
        }

        # Validate the file size (Warning: the largest files supported by this code is 2GB)
        $file_size = @filesize($_FILES[$upload_name]["tmp_name"]);
        if (!$file_size || $file_size > $max_file_size_in_bytes) {
            return self::swfuploadError(_t('swfupload', 'Размер файла превышает допустимый'), $bReturnErrors);
        }
        if ($file_size <= 0) {
            return self::swfuploadError(_t('swfupload', 'Недопустимый размер файла'), $bReturnErrors);
        }

        # Validate file name (for our purposes we'll just remove invalid characters)
        $file_name = preg_replace('/[^' . $valid_chars_regex . ']|\.+$/i', "", basename($_FILES[$upload_name]['name']));
        if (strlen($file_name) == 0 || strlen($file_name) > $MAX_FILENAME_LENGTH) {
            return self::swfuploadError(_t('swfupload', 'Недопустимое имя файла'), $bReturnErrors);
        }

        // Validate file extension
        $file_extension = bff\utils\Files::getExtension($_FILES[$upload_name]['name']);
        if (!empty($extension_whitelist)) {
            $is_valid_extension = false;
            foreach ($extension_whitelist as $extension) {
                if (strcasecmp($file_extension, $extension) == 0) {
                    $is_valid_extension = true;
                    break;
                }
            }
            if (!$is_valid_extension) {
                return self::swfuploadError(_t('swfupload', 'Недопустимый тип файла'), $bReturnErrors);
            }
        }

        $aResult = array(
            'ext'        => $file_extension,
            'size'       => $file_size,
            'tmp_name'   => $_FILES[$upload_name]['tmp_name'],
            'field_name' => $upload_name,
            'image'      => false
        );

        if (!empty($only_images)) {
            $res = getimagesize($_FILES[$upload_name]['tmp_name']);
            if (empty($res) || !isset($res[2])) {
                return self::swfuploadError(_t('swfupload', 'Недопустимый тип файла'), $bReturnErrors);
            }
            if (is_array($only_images) && !in_array($res[2], $only_images)) {
                return self::swfuploadError(_t('swfupload', 'Недопустимый тип файла'), $bReturnErrors);
            }
            $aResult['image'] = true;
            $aResult['width'] = $res[0];
            $aResult['height'] = $res[1];
        }

        return $aResult;
    }

    /**
     * Формирование результата загрузки файла через SWFUpload
     * @param string $sFilename имя загруженного файла
     */
    public static function swfuploadFinish($sFilename)
    {
        echo 'FILEID:' . $sFilename;
        exit(0);
    }

    /**
     * Формируем ошибку загрузки файла через SWFUpload
     * @param string $sMessage текст ошибки
     * @param bool $bReturnErrors возвращать текст ошибки
     * @return mixed
     */
    public static function swfuploadError($sMessage, $bReturnErrors = false)
    {
        if (!$bReturnErrors) {
            header("HTTP/1.1 500 Internal Server Error");
            echo 'Error: ' . $sMessage;
            exit(0);
        } else {
            return $sMessage;
        }
    }

}