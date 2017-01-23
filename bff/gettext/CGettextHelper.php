<?php

/**
 * Класс работы с файлами локализаций gettext
 * @version 0.35
 * @modified 2.sep.2014
 */

use bff\utils\Files;

class CGettextHelper
{
    /** @var \bff\base\Locale object */
    protected $locale = null;

    /** @var integer максимальное кол-во бекапов *.pot файла */
    protected $poFileMaxBackups = 3;
    /** @var CGettextPoFile object */
    protected $poFileObj = null;

    /** @var string регулярное выражение для поиска конструкций {t}{/t} в шаблонах smarty */
    protected $exprTPL = '';
    /** @var string регулярное выражение для поиска конструкций _t() в php файлах */
    protected $exprPHP = '';

    /** @var string имя pot файла */
    const POT_FILENAME = 'msg';
    
    public function __construct()
    {
        $this->locale = bff::locale();

        require_once PATH_CORE.'gettext/CGettextPoFile.php';
        $this->poFileObj = new CGettextPoFile();
    }

    /**
     * Поиск строк перевода + формирование файлов перевода (*.pot)
     * @param string $rootPath
     * @param array $locales
     * @param array $exclude
     * @return bool
     */
    public function generatePotFiles($rootPath, array $locales, array $exclude = array('.svn', '.idea', '/tpl_c'))
    {
        # TPL parse expression
        $tLeftQ = preg_quote('{'); # smarty open tag
        $tRightQ = preg_quote('}'); # smarty close tag
        $tCmd = preg_quote('t'); # smarty command
        $this->exprTPL = "/{$tLeftQ}({$tCmd})\s([^{$tRightQ}]*)c=[\'\"]([^{$tRightQ}]*)[\'\"]\s*([^{$tRightQ}]*){$tRightQ}([^{$tLeftQ}]*){$tLeftQ}\/\\1{$tRightQ}/";
        
        # PHP parse expression
        $translator = '_t';
        //$this->exprPHP = '/\b'.$translator.'\(\s*(\'.*?(?<!\\\\)\'|".*?(?<!\\\\)")\s*[,\)]/s';
        //$this->exprPHP = '/\b'.$translator.'\s*\(\s*(\'.*?(?<!\\\\)\'|".*?(?<!\\\\)")\s*,\s*(\'.*?(?<!\\\\)\'|".*?(?<!\\\\)")\s*[,\)]/s';  
        $this->exprPHP = '/\b'.$translator.'\s*\(\s*\'(.*?)\'\s*,\s*[\'\"](.*?)[\'\"]\s*[,\)]/s'; // 2 params: ('context', 'text'...
        
        $files = Files::getFiles($rootPath, '', true, true, array('tpl', 'php'), $exclude);

        $messages = array();    
        foreach($files as $file) {
            $messages = array_merge($messages, $this->parseMessages($file, Files::getExtension($file) ) );
        }
        
        $messages = array_unique($messages);

        $updated = false;
        foreach($locales as $localeKey=>$localeData)
        {
            if( $this->savePoFile($messages, $this->locale->gt_Path($localeKey), true) )
                $updated = true;
        }

        return $updated;
    }

    /**
     * Обновление *.pot файлов на основе загруженных данных из *.pot файлов
     * @param array $uploadedFiles
     */
    public function updatePoFiles($uploadedFiles)
    {
        foreach($uploadedFiles as $localeKey=>$fileName)
        {
            $messages = $this->poFileObj->load($fileName);
            # сравниваем с текущим файлом, может чего потеряли, делаем ротацию, обновляем
            $this->savePoFile($messages, $this->locale->gt_Path($localeKey), false);
        }
    }

    /**
     * Формирование *.mo файлов
     * @param array $locales
     * @param string $moCurrentID
     * @param string $moNextID
     */
    public function updateMoFiles($locales, $moCurrentID = '', $moNextID = '')
    {
        require_once PATH_CORE.'gettext/CGettextMoFile.php';
        $moFileObj = new CGettextMoFile(false);

        # обновляем *.mo файлы основываясь на *.pot файлах
        foreach($locales as $localeKey=>$localeData)
        {
            $langDir = $this->locale->gt_Path($localeKey);

            # 1) FRONTEND:

            # загружаем сообщения из текущего *.pot файла
            $messages = $this->poFileObj->load( self::getPOFilename( $langDir ), array('', 'common') );
            ksort( $messages );

            # обновляем
            $moCurrentFile = $langDir.DIRECTORY_SEPARATOR.$moCurrentID.'-'.$localeKey.'.mo';
            if(is_file($moCurrentFile)) {
                $moFileObj->save($moCurrentFile, $messages);
                if( ! empty($moNextID)) # меняем имя *.mo файла, если было указано новое
                    rename($moCurrentFile, $langDir.DIRECTORY_SEPARATOR.$moNextID.'-'.$localeKey.'.mo');
            } else { # создаем новый
                $moFileObj->save("$langDir".DIRECTORY_SEPARATOR."$moNextID-$localeKey.mo", $messages);
            }

            # 2) ADMIN:

            # загружаем сообщения из текущего *.pot файла
            $messages = $this->poFileObj->load( self::getPOFilename( $langDir ), array('', 'admin', 'common') );
            ksort( $messages );

            # обновляем
            $moCurrentFile = $langDir.DIRECTORY_SEPARATOR.$moCurrentID.'-'.$localeKey.'-admin.mo';
            if(is_file($moCurrentFile)) {
                $moFileObj->save($moCurrentFile, $messages);
                if( ! empty($moNextID)) # меняем имя *.mo файла, если было указано новое
                    rename($moCurrentFile, $langDir.DIRECTORY_SEPARATOR.$moNextID.'-'.$localeKey.'-admin.mo');
            } else { # создаем новый
                $moFileObj->save($langDir.DIRECTORY_SEPARATOR.$moNextID.'-'.$localeKey.'-admin.mo', $messages);
            }
        }
    }

    /**
     * Поиск строк перевода в файле
     * @param string $fileName имя файла
     * @param string $fileExtension расширение файла
     * @return array
     */
    protected function parseMessages($fileName, $fileExtension)
    {
        $content = @file_get_contents($fileName);
        if(empty($content)) return array();

        $messages = array();
        $moduleSeparator = '|';
        if($fileExtension == 'tpl')
        {      
            $n = preg_match_all($this->exprTPL, $content, $matches, PREG_SET_ORDER);
            # 1 - func name, 3 - module, 4 - params, 4 - text
            if($n>0) 
            {
                $context = $this->getContextByFilepath($fileName);
                for($i=0; $i<$n; ++$i) {
                    $messages[] = $context.($matches[$i][3]!='' ? $matches[$i][3].$moduleSeparator : '').$this->fix($matches[$i][5]);
                }
            }
        }
        elseif($fileExtension == 'php') 
        {
            $n = preg_match_all($this->exprPHP, $content, $matches, PREG_SET_ORDER);
            # 1 - module, 2 - text
            if($n>0) {
                $context = $this->getContextByFilepath($fileName); 
                for($i=0; $i<$n; ++$i) {
                    $messages[] = $context.($matches[$i][1]!='' ? $matches[$i][1].$moduleSeparator : '').$matches[$i][2];
                }
            }
        }
        return $messages;
    }

    /**
     * Определение контекста по названию файла
     * @param string $sFilepath полный путь к файлу
     * @return string
     */
    public function getContextByFilepath($sFilepath)
    {
        if(strpos($sFilepath, 'admin.') !== false ||
           strpos($sFilepath, 'adm.')  !== false) {
            return "admin\004";
        } elseif(strpos($sFilepath, '/bff/') !== FALSE) {
            return "common\004";
        }
        return ''; # frontend
    }

    /**
     * "fix" string - strip slashes, escape and convert new lines to \n
     * @param string $str
     * @return string
     */
    protected function fix($str)
    {
        $str = stripslashes($str);
        $str = str_replace('"', '\"', $str);
        $str = str_replace("\n", '\n', $str);
        return $str;
    }

    /**
     * Сохранение *.pot файла
     * @param array $messages
     * @param string $dir
     * @param array $keysOnly
     * @return bool
     */
    protected function savePoFile($messages, $dir, $keysOnly)
    {
        $fileName = self::getPOFilename($dir);
        
        if(is_file($fileName)) # обновляем существующий msg.pot файл
        {
            $translated = $this->poFileObj->load($fileName); 
            ksort($translated); 
            
            if($keysOnly) {
                sort($messages);
                if(array_keys($translated) == $messages) { # ничего нового
                    return false;
                }
            } else {
                ksort($messages);
                if($translated == $messages) { # ничего нового
                    return false;
                }
            }
            
            $merged = array();
            $untranslated = array();
            if($keysOnly) {
                foreach($messages as $key)
                {
                    if( ! empty($translated[$key]))
                        $merged[$key] = $translated[$key];
                    else
                        $untranslated[$key] = '';
                }
            } else {
                foreach($messages as $key=>$value)
                {
                    if(isset($translated[$key])) {
                        if($value !== '') {
                            $merged[$key] = $value;
                        } else {
                            $merged[$key] = $translated[$key];
                        }
                    }
                    else
                        $untranslated[$key] = '';
                }
            }
            
            ksort($merged); 
            ksort($untranslated);
            $merged = array_merge($untranslated, $merged);

            $this->backupFile(self::getPOFilename($dir), $this->poFileMaxBackups);
        } else { # создаем новые
            $merged = array();
            if($keysOnly) {
                foreach($messages as $message)
                    $merged[$message] = '';   
            } else {
                foreach($messages as $key=>$msg)
                    $merged[$key] = $msg;
            }
        }
        
        ksort($merged);

        $this->poFileObj->save($fileName, $merged);
        return true;
    }

    /**
     * Ротация файлов
     * @param string $file путь к файлу
     * @param integer $max масимальное кол-во ротаций
     */
    protected function backupFile($file, $max)
    {
        for($i=$max; $i>0; --$i)
        {
            $rotateFile = $file.'.'.$i;
            if(is_file($rotateFile))
            {
                if($i === $max)
                    @unlink($rotateFile);
                else
                    @rename($rotateFile, $file.'.'.($i+1));
            }
        }
        if(is_file($file))
            rename($file, $file.'.1');
    }

    /**
     * Формирование путь к файлу *.pot
     * @param string $localeDir путь к директории хранения *.pot файла
     * @param string $extension расширение
     * @return string
     */
    public static function getPOFilename($localeDir, $extension = '.pot')
    {
        return $localeDir . DIRECTORY_SEPARATOR . self::POT_FILENAME . $extension;
    }

}