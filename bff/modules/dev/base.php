<?php

require_once 'model.php';

use bff\utils\Files;

abstract class DevModuleBase extends Module
{
    /** @var DevModelBase */
    public $model = null;
    protected $securityKey = 'cf145907d6219342039a032a3c2bc25c';

    /**
     * Создаем базовую структуру модуля
     * @param string $sTitle название модуля на англ.
     * @param string $sName название модуля на русском
     * @param string|bool $mInstallMarker маркер для CRUD-генератора или FALSE
     * @return bool true - модуль был успешно создан, false - ошибки при создании модуля
     */
    protected function createModule($sTitle, $sName, $mInstallMarker = false)
    {
        $nChmod = 0775;
        $bSystemError = true;
        $aLanguages = array('def');

        do {
            if (empty($sTitle)) {
                $this->errors->set('Укажите название модуля (на английском)');
                break;
            }

            $sModuleName = tpl::ucfirst($sTitle);
            $sModuleFileName = mb_strtolower($sModuleName);
            $sModuleDirectory = PATH_MODULES . $sModuleFileName . DS;

            if (file_exists($sModuleDirectory . $sModuleName . '.class.php')) {
                $this->errors->set('Модуль с таким названием уже существует', $bSystemError);
                break;
            }

            if (!Files::makeDir($sModuleDirectory, $nChmod)) {
                $this->errors->set(sprintf('Невозможно создать директорию "%s"', PATH_MODULES . $sModuleFileName), $bSystemError);
                break;
            }

            # create Template Directories
            if (!Files::makeDir($sModuleDirectory . 'tpl', $nChmod)) {
                $this->errors->set(sprintf('Невозможно создать директорию "%s"', $sModuleDirectory . 'tpl'), $bSystemError);
                break;
            }
            foreach ($aLanguages as $lng) {
                Files::makeDir($sModuleDirectory . 'tpl' . DS . $lng . DS, $nChmod);
            }

            # create Files
            $aFiles = array(
                # BL
                $sModuleFileName . '.bl.class.php'     => "<?php\n\nabstract class {$sModuleName}Base extends Module\n{\n    /** @var {$sModuleName}Model */\n    var \$model = null;\n    var \$securityKey = '" . md5(uniqid($sModuleName)) . "';\n\n\n}",
                # Model
                $sModuleFileName . '.model.php'        => "<?php\n\nclass {$sModuleName}Model extends Model\n{\n    /** @var {$sModuleName}Base */\n    var \$controller;\n    \n\n}",
                # Menu
                'm.' . $sModuleFileName . '.class.php' => "<?php\n\nclass M_$sModuleName\n{\n    static function declareAdminMenu(CMenu \$menu, Security \$security)\n    {\n        \$menu->assign('{$sName}', 'Список', '$sModuleFileName', 'listing', true, 1);\n        \n    }\n}",
                # Install.sql
                'install.sql'                          => '',
                # Admin
                $sModuleFileName . '.adm.class.php'    => "<?php\n\nclass $sModuleName extends {$sModuleName}Base\n{\n\n\n}",
                # Frontend
                $sModuleFileName . '.class.php'        => "<?php\n\nclass $sModuleName extends {$sModuleName}Base\n{\n\n\n}"
            );

            foreach ($aFiles as $file => $fileContent) {
                if (!Files::putFileContent($sModuleDirectory . $file, $fileContent)) {
                    $this->errors->set(sprintf('Невозможно создать файл "%s"', $file), $bSystemError);
                    break;
                }
            }

            return true;

        } while (false);

        return false;
    }

    /**
     * Проверка списка директорий/файлов на наличие прав записи
     * @param boolean $consoleContext вызываем в контексте CLI
     * @return array
     */
    public function writableCheckProcess($consoleContext = false)
    {
        # список директорий / файлов по-умолчанию
        $check = array();

        # формируем список директорий / файлов требующих проверки прав записи
        $modulesList = bff::i()->getModulesList('all');
        foreach ($modulesList as $m) {
            $ex = bff::module($m)->writableCheck();
            if (!empty($ex)) $check = array_merge($check, $ex);
        }
        ksort($check);

        # учитываем настройку open_basedir
        $openBaseDir = (string)ini_get('open_basedir');
        if (!empty($openBaseDir)) {
            $openBaseDir = explode(PATH_SEPARATOR, $openBaseDir);
            if (!empty($openBaseDir) && is_array($openBaseDir)) {
                $openBaseDirAdd = array();
                foreach ($openBaseDir as $v) {
                    $v = rtrim($v, DIRECTORY_SEPARATOR);
                    if (file_exists($v) && is_link($v)) {
                        $v = readlink($v);
                        if ($v !== false) {
                            $openBaseDirAdd[] = $v;
                        }
                    }
                }
                if (!empty($openBaseDirAdd)) {
                    $openBaseDir = array_merge($openBaseDir, $openBaseDirAdd);
                }
            }
        }

        clearstatcache();
        $checkList = array();
        foreach ($check as $file=>$type)
        {
            $v = array('path' => rtrim(str_replace(PATH_BASE, DS, $file), DS), 'access' => 0);

            # open_basedir
            if (!empty($openBaseDir)) {
                $open = false;
                foreach ($openBaseDir as $vv) {
                    $vv = rtrim($vv, DS) . DS;
                    $vv_file = ( file_exists($file) && is_file($file) ? $file : rtrim($file, DS).DS );
                    if (mb_stripos($vv_file, $vv) === 0) {
                        $open = true; break;
                    }
                }
                if ( ! $open) {
                    $v['access'] = 3; # Ограничение open_basedir
                    $checkList[] = $v;
                    continue;
                }
            }

            if (is_array($type)) {
                $type_res = 'dir';
                foreach ($type as $k=>$vv) {
                    if (!isset($v[$k])) $v[$k] = $vv;
                    if ($k == 'type') $type_res = $vv;
                }
                $type = $type_res;
            }

            switch($type)
            {
                case 'dir':
                case 'file':
                {
                    if (!file_exists($file)) {
                        $v['access'] = 1; # Не существует
                        $checkList[] = $v;
                        break;
                    }
                    if (!is_writable($file)) {
                        $v['access'] = 2; # Нет прав на запись
                        $checkList[] = $v;
                        break;
                    }
                    if ($type == 'dir') {
                        $success = true;
                        try {
                            $dirsIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($file, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
                            foreach($dirsIterator as $f)
                            {
                                if ($f->isFile()) continue;
                                $f_path = $f->getPathname();
                                if (!is_writable($f_path)) {
                                    $checkList[] = array('path' => rtrim(str_replace(PATH_BASE, DS, $f_path), DS), 'access' => 2);
                                    $success = false;
                                }
                            }
                        } catch (\Exception $e) {
                            if (mb_stripos($e->getMessage(), 'Permission denied') === false) {
                                $this->errors->set($e->getMessage());
                            }
                        }
                        if (!$success) {
                            continue;
                        }
                    }
                    $checkList[] = $v;
                } break;
                case 'file-e': {
                    if (file_exists($file) && !is_writable($file)) {
                        $v['access'] = 2; # Нет прав на запись
                    }
                    $checkList[] = $v;
                } break;
                case 'dir-only': {
                    if (file_exists($file) && !is_writable($file)) {
                        $v['access'] = 2; # Нет прав на запись
                    }
                    $checkList[] = $v;
                } break;
                case 'dir-split': {
                    $file = rtrim($file, DS).DS;
                    if (!file_exists($file)) {
                        $v['access'] = 1; # Не существует
                        $checkList[] = $v;
                    } else {
                        $success = true;
                        try {
                            $dirsIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($file, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
                            foreach($dirsIterator as $f)
                            {
                                if ($f->isFile() && !is_numeric($f->getFilename())) continue;
                                $f_path = $f->getPathname();
                                if (!is_writable($f_path)) {
                                    $checkList[] = array('path' => rtrim(str_replace(PATH_BASE, DS, $f_path), DS), 'access' => 2);
                                    $success = false;
                                }
                            }
                        } catch (\Exception $e) {
                            if (mb_stripos($e->getMessage(), 'Permission denied') === false) {
                                $this->errors->set($e->getMessage());
                            }
                        }
                        if ($success) {
                            $v['access'] = 0; # OK
                            $checkList[] = $v;
                        }
                    }
                } break;
                case 'dir+files':
                {
                    if (!file_exists($file)) {
                        $v['access'] = 1; # Не существует
                        $checkList[] = $v;
                        break;
                    }
                    if (!is_writable($file)) {
                        $v['access'] = 2; # Нет прав на запись
                        $checkList[] = $v;
                        break;
                    }
                    $success = true;
                    try {
                        $dirsIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($file, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
                        foreach($dirsIterator as $f)
                        {
                            if (!$f->isFile()) continue;
                            $f_path = $f->getPathname();
                            if (!is_writable($f_path)) {
                                $checkList[] = array('path' => rtrim(str_replace(PATH_BASE, DS, $f_path), DS), 'access' => 2);
                                $success = false;
                            }
                        }
                    } catch (\Exception $e) {
                        if (mb_stripos($e->getMessage(), 'Permission denied') === false) {
                            $this->errors->set($e->getMessage());
                        }
                    }
                    if (!$success) {
                        continue;
                    }
                    $checkList[] = $v;
                } break;
            }
        }

        $result = array(
            'list' => $checkList,
            'accessTypes' => array(
                0 => array('t' => 'OK', 'class' => 'clr-success', 'color'=>'green'),
                1 => array('t' => 'Не существует', 'class' => 'clr-error', 'color'=>'red'),
                2 => array('t' => 'Нет прав на запись', 'class' => 'clr-error', 'color'=>'red'),
                3 => array('t' => 'Ограничение open_basedir', 'class' => 'clr-error', 'color'=>'red'),
            )
        );

        if ($consoleContext) {
            $colors = array('green'=>'0;32', 'red'=>'0;31', 'grey'=>'0;33');
            foreach ($checkList as $v) {
                $access = $result['accessTypes'][$v['access']];
                $access = ( isset($colors[$access['color']]) ? "\033[" . $colors[$access['color']] . "m".$access['t']."\033[0m" : $access['t'] );
                echo $v['path'] .(!empty($v['title']) ? " \033[" . $colors['grey'] . "m(".$v['title'].")\033[0m" : '').': ' . $access . "\r\n";
            }
        } else {
            return $result;
        }
    }

    /**
     * Статистика использования ресурсов системы
     */
    public function debugStatistic()
    {
        # время обработки скрипта
        $processTime = round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']), 5);

        # пиковое использование памяти
        $memoryPeak = memory_get_peak_usage();

        # кол-во запросов к базе данных
        $dbStatistic = $this->db->statGet();
        $dbQueryCount = 0;
        $dbQueryTime = 0;
        $dbQueryCached = 0;
        $dbQueryCachedTime = 0;
        if (!empty($dbStatistic['query'])) {
            foreach ($dbStatistic['query'] as &$v) {
                if (empty($v['n'])) continue;
                if (!empty($v['c'])) {
                    $dbQueryCached += $v['c'];
                    $dbQueryCachedTime += $v['ctt'];
                    if ($v['n'] === $v['c']) continue;
                    $v['n'] -= $v['c'];
                }
                $dbQueryCount += $v['n'];
                $dbQueryTime  += $v['tt'];
            } unset($v);
        }

        return array(
            'process_time' => $processTime,
            'memory_peak' => $memoryPeak,
            'database_queries'        => $dbQueryCount,
            'database_queries_time'   => $dbQueryTime,
            'database_queries_cached' => $dbQueryCached,
            'database_queries_cached_time' => $dbQueryCachedTime,
        );
    }

    /**
     * Формирование списка директорий/файлов требующих проверки на наличие прав записи
     * @return array
     */
    public function writableCheck()
    {
        return array_merge(parent::writableCheck(), array(
            PATH_BASE.'files'.DS.'cache'  => 'dir', # кеш
            PATH_BASE.'files'.DS.'logs'   => 'dir+files', # логи
            PATH_BASE.'files'.DS.'smarty' => 'dir', # шаблоны smarty
        ));
    }
}