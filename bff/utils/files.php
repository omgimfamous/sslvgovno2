<?php namespace bff\utils;

/**
 * Класс для работы с директориями / файлами
 * @abstract
 * @version 0.631
 * @modified 8.jun.2015
 */

abstract class Files
{
    /**
     * Формирование списка директорий относительно корневой директории
     * @param string $sPath путь к корневой директории
     * @return array
     */
    public static function getDirs($sPath)
    {
        $aResult = array();
        foreach (new \DirectoryIterator($sPath) as $file) {
            if ($file->isDir() && !$file->isDot()) {
                $aResult[] = $file->getFilename();
            }
        }

        return $aResult;
    }

    /**
     * Подсчет размера файлов(поддиректорий) в директории
     * @param mixed $sPath путь к директории
     * @return integer
     */
    public static function getDirSize($sPath)
    {
        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($sPath)) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

    /**
     * Рекурсивный обход директорий
     * @param string $directory корневая директория
     * @param string $base путь относительно корневой директории
     * @param boolean $bRecursive рекурсивный обход
     * @param boolean $bReturnFullPath true - возвращать полный путь, false - только имя файла/директории
     * @param array $aFileTypes типы файлов
     * @param array $aExclude список исключений
     * @return array
     */
    public static function getFiles($directory, $base = '', $bRecursive = true, $bReturnFullPath = true, array $aFileTypes = array(), array $aExclude = array())
    {
        if (is_file($directory) || !is_dir($directory)) {
            return false;
        }

        $directory = rtrim($directory, '\\/');
        if (!is_dir($directory)) {
            return false;
        }

        # Открываем директорию
        if ($dir = opendir($directory)) {
            # Формируем список найденных файлов
            $tmp = Array();
            # Добавляем файлы
            while ($file = readdir($dir)) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $isFile = is_file($directory . '/' . $file);
                if (!static::validatePath($base, $file, $isFile, $aFileTypes, $aExclude)) {
                    continue;
                }

                if ($isFile) {
                    array_push($tmp, ($bReturnFullPath ? $directory . '/' . $file : $file));
                } else {
                    # Если директория > ищем в ней
                    if ($bRecursive) {
                        $tmpSub = static::getFiles($directory . '/' . $file, $base . '/' . $file, $bRecursive, $bReturnFullPath, $aFileTypes, $aExclude);
                        if (!empty($tmpSub)) {
                            $tmp = array_merge($tmp, $tmpSub);
                        }
                    }
                }
            }
            closedir($dir);

            return $tmp;
        }

        return array();
    }

    /**
     * Валидация пути файла / директории
     * @param string $base путь относительно корневой директории
     * @param string $file имя файла / директории
     * @param boolean $bIsFile
     * @param array $aFileTypes список суфиксов типов файлов (без точки). Проходят только файлы с указанными суфиксами.
     * @param array $aExclude список исключений
     * @return boolean true - файл / директория валидны
     */
    protected static function validatePath($base, $file, $bIsFile, array $aFileTypes, array $aExclude)
    {
        foreach ($aExclude as $e) {
            if ($file === $e || strpos($base . '/' . $file, $e) === 0) {
                return false;
            }
        }
        if (!$bIsFile || empty($aFileTypes)) {
            return true;
        }
        if (($pos = strrpos($file, '.')) !== false) {
            $type = substr($file, $pos + 1);

            return in_array($type, $aFileTypes);
        } else {
            return false;
        }
    }

    /**
     * Получение содержимого файла в виде строки
     * @param string $sFilePath путь к файлу
     * @return string
     */
    public static function getFileContent($sFilePath)
    {
        return file_get_contents($sFilePath);
    }

    /**
     * Запись строки в файл
     * @param string $sFilePath путь к файлу
     * @param string $sContent данные
     * @return boolean
     */
    public static function putFileContent($sFilePath, $sContent)
    {
        $res = file_put_contents($sFilePath, $sContent);

        return ($res !== false);
    }

    /**
     * Достаточно ли прав на запись
     * @param string $sPath путь к файлу / директории
     * @param boolean $bTriggerError вызывать пользовательскую ошибку
     * @return boolean
     */
    public static function haveWriteAccess($sPath, $bTriggerError = false)
    {
        if (!is_writable($sPath) && !chmod($sPath, 775)) {
            if ($bTriggerError) {
                trigger_error(sprintf('Unable to write to "%s"', realpath((is_dir($sPath) ? $sPath : dirname($sPath)))));
            }

            return false;
        }

        return true;
    }

    /**
     * Чистим имя файла от запрещенных символов
     * @param string $sFileName имя файла
     * @param boolean $bRelativePath относительный путь (true)
     * @return string очищенное имя файла
     */
    public static function cleanFilename($sFileName, $bRelativePath = false)
    {
        $bad = array(
            '../',
            '<!--',
            '-->',
            '<',
            '>',
            "'",
            '"',
            '&',
            '$',
            '#',
            '{',
            '}',
            '[',
            ']',
            '=',
            ';',
            '?',
            "%20",
            "%22",
            "%3c", // <
            "%253c", // <
            "%3e", // >
            "%0e", // >
            "%28", // (
            "%29", // )
            "%2528", // (
            "%26", // &
            "%24", // $
            "%3f", // ?
            "%3b", // ;
            "%3d", // =
        );

        if (!$bRelativePath) {
            $bad[] = './';
            $bad[] = '/';
        }

        return stripslashes(str_replace($bad, '', $sFileName));
    }

    /**
     * Создание директории
     * @param string $sDirectoryName имя директории
     * @param int $nPermissions права (@see chmod)
     * @param bool $bRecursive рекурсивное создание директории
     * @return bool
     **/
    public static function makeDir($sDirectoryName, $nPermissions = 0775, $bRecursive = false)
    {
        $parent = dirname($sDirectoryName);
        if (!static::haveWriteAccess($parent, true)) {
            return false;
        }

        $umask = umask(0);
        $res = mkdir($sDirectoryName, $nPermissions, $bRecursive);
        umask($umask);
        return $res;
    }

    /**
     * Получение расширения файла (без точки)
     * @param string $sPath путь к файлу
     * @return string расширение без точки
     */
    public static function getExtension($sPath)
    {
        $res = mb_strtolower(pathinfo($sPath, PATHINFO_EXTENSION));

        return ($res == 'jpeg' ? 'jpg' : $res);
    }

    /**
     * Проверка, является ли файл изображением
     * @param mixed $sPath путь к файлу
     * @param boolean $bCheckExtension проверять расширение файла
     * @return boolean
     */
    public static function isImageFile($sPath, $bCheckExtension = false)
    {
        if ($bCheckExtension && !in_array(static::getExtension($sPath), array('gif', 'jpg', 'png'))) {
            return false;
        }

        $imSize = getimagesize($sPath);
        if (empty($imSize)) {
            return false;
        }
        if (in_array($imSize[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG))) {
            return true;
        }

        return false;
    }

    /**
     * Загрузка файла по URL
     * @param string $url URL файла
     * @param string $path полный путь для сохранения файла
     * @param boolean $setErrors фиксировать ошибки
     * @return boolean файл был успешно загружен, false - ошибка загрузки файла
     */
    public static function downloadFile($url, $path, $setErrors = true)
    {
        if (empty($url)) {
            if ($setErrors) {
                \bff::errors()->set(_t('system', 'URL указан некорректно'));
            }

            return false;
        }
        $dir = $path;
        if (!is_dir($dir)) {
            $dir = pathinfo($dir, PATHINFO_DIRNAME);
        }
        if (!is_writable($dir)) {
            if ($setErrors) {
                \bff::errors()->set(_t('system', 'Укажите путь к директории, доступной для записи'));
            }

            return false;
        }

        if (extension_loaded('curl')) {
            $file = fopen($path, 'w+');
            $max_redirects = 5;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_FAILONERROR, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, $max_redirects);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            if (ini_get('open_basedir') !== '')  # fix CURLOPT_FOLLOWLOCATION + open_basedir
            {
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
                $url2 = $url_original = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

                $ch2 = curl_copy_handle($ch);
                curl_setopt($ch2, CURLOPT_HEADER, 1);
                curl_setopt($ch2, CURLOPT_NOBODY, 1);
                curl_setopt($ch2, CURLOPT_FORBID_REUSE, 0);
                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
                do
                {
                    curl_setopt($ch2, CURLOPT_URL, $url2);
                    $header = curl_exec($ch2);
                    if (curl_errno($ch2)) {
                        $code = 0;
                    } else {
                        $code = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                        if ($code == 301 || $code == 302) {
                            preg_match('/Location:(.*?)\n/i', $header, $matches);
                            $url2 = trim(array_pop($matches));

                            // if no scheme is present then the new url is a
                            // relative path and thus needs some extra care
                            if (!preg_match("/^https?:/i", $url2)) {
                                $url2 = $url_original . $url2;
                            }
                        } else {
                            $code = 0;
                        }
                    }
                } while ($code && --$max_redirects);
                curl_close($ch2);

                curl_setopt($ch, CURLOPT_URL, $url2);
            } else {
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            }
            curl_setopt($ch, CURLOPT_FILE, $file);

            $res = curl_exec($ch);
            curl_close($ch);

            fclose($file);
        } elseif (ini_get('allow_url_fopen')) {
            $res = file_put_contents($path, fopen($url, 'r'));
        } else {
            $res = false;
        }

        if (empty($res)) {
            if ($setErrors) {
                \bff::errors()->set(_t('system', 'Ошибка загрузки файла'));
            }

            return false;
        }

        return true;
    }

    /**
     * Проверка прав записи в директорию/файл
     * @param array $files список директорий/файлов для проверки наличия прав записи
     * @param bool $onlyFordev выполнять проверку только при включенном режиме разработчика
     */
    public static function writableCheck(array $files, $onlyFordev = true)
    {
        if ($onlyFordev && !FORDEV) {
            return;
        }
        if (empty($files)) {
            return;
        }
        foreach ($files as $v) {
            if (empty($v)) continue;
            if (!file_exists($v)) {
                \bff::errors()->set('Проверьте наличие директории/файла "'.str_replace(PATH_BASE, DIRECTORY_SEPARATOR, $v).'"')->autohide(false);
            } else {
                if (!is_writable($v)) {
                    \bff::errors()->set('Недостаточно прав на запись в '.(is_dir($v) ? 'директорию' : 'файл').' "'.str_replace(PATH_BASE, DIRECTORY_SEPARATOR, $v).'"')->autohide(false);
                }
            }
        }
    }

}