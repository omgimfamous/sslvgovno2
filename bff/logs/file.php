<?php namespace bff\logs;

/**
 * Логирование записей в файл
 * @version 0.31
 * @modified 2.apr.2014
 */

class File
{
    /** @var int Максимальный размер log-файлов (в КБ) */
    private $maxFileSize = 1024;

    /** @var int Кол-во файлов ротации log-файлов */
    private $maxLogFiles = 2;

    /** @var string Директория log-файлов */
    private $logPath;

    /** @var string Название log-файла */
    private $logFile = 'app.log';

    /**
     * Конструктор
     * @param string $sPath директория log-файлов
     * @param string $sFilename название log-файла
     * @param integer $nMaxFileSize максимальный размер log-файлов (в КБ)
     * @param integer $nMaxLogFiles кол-во файлов ротации log-файлов
     */
    public function __construct($sPath, $sFilename, $nMaxFileSize = 1024, $nMaxLogFiles = 2)
    {
        if ($this->getLogPath() === null) {
            $this->setLogPath($sPath);
        }

        $this->setLogFile($sFilename);
        $this->setMaxFileSize($nMaxFileSize);
        $this->setMaxLogFiles($nMaxLogFiles);
    }

    /**
     * Получение директории хранения log-файлов
     * @return string
     */
    public function getLogPath()
    {
        return $this->logPath;
    }

    /**
     * Назначение директории хранения log-файлов
     * @param string $value директория
     */
    public function setLogPath($value)
    {
        $this->logPath = realpath($value);
        if ($this->logPath === false || !is_dir($this->logPath) || !is_writable($this->logPath)) {
            trigger_error('CFileLogJSRoute.logPath "' . $value . '" does not point to a valid directory. Make sure the directory exists and is writable by the Web server process.');
        }
    }

    /**
     * Получаем имя log-файла
     * @return string
     */
    public function getLogFile()
    {
        return $this->logFile;
    }

    /**
     * Назначаем имя log-файла
     * @param string $value log-файл
     */
    public function setLogFile($value)
    {
        $this->logFile = $value;
    }

    /**
     * Получение текущего значения масимально допустимого размера log-файлов
     * @return integer объем файла, в килобайтах
     */
    public function getMaxFileSize()
    {
        return $this->maxFileSize;
    }

    /**
     * Назначаем максимальный размер log-файлов (в КБ)
     * @param integer $value размер (в КБ)
     */
    public function setMaxFileSize($value)
    {
        if (($this->maxFileSize = (int)$value) < 1) {
            $this->maxFileSize = 1;
        }
    }

    /**
     * Получаем кол-во файлов ротации log-файлов
     * @return integer
     */
    public function getMaxLogFiles()
    {
        return $this->maxLogFiles;
    }

    /**
     * Назначаем кол-во файлов ротации log-файлов
     * @param integer $value кол-во
     */
    public function setMaxLogFiles($value)
    {
        if (($this->maxLogFiles = (int)$value) < 1) {
            $this->maxLogFiles = 1;
        }
    }

    /**
     * Логирование записи
     * @param string $message сообщение
     * @param string $level уровень сообщения: 'Trace', 'Warning', 'Error'
     * @param string $category категория сообщения
     */
    public function log($message, $level = 'info', $category = '*')
    {
        $logFile = $this->getLogPath() . DIRECTORY_SEPARATOR . $this->getLogFile();
        if (file_exists($logFile) && @filesize($logFile) > ($this->getMaxFileSize() * 1024)) {
            $this->rotateFiles();
        }

        error_log(@gmdate('Y/m/d H:i:s', microtime(true)) . " $message" . PHP_EOL, 3, $logFile);
    }

    /**
     * Ротация файлов
     */
    protected function rotateFiles()
    {
        $file = $this->getLogPath() . DIRECTORY_SEPARATOR . $this->getLogFile();
        $max = $this->getMaxLogFiles();
        for ($i = $max; $i > 0; --$i) {
            $rotateFile = $file . '.' . $i;
            if (is_file($rotateFile)) {
                if ($i === $max) {
                    @unlink($rotateFile);
                } else {
                    @rename($rotateFile, $file . '.' . ($i + 1));
                }
            }
        }
        if (is_file($file)) {
            rename($file, $file . '.1');
        }
    }
}