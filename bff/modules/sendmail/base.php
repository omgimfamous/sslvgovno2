<?php

require_once 'model.php';

use bff\utils\Files;

abstract class SendmailModuleBase extends Module
{
    /** @var SendmailModelBase */
    public $model = null;
    protected $securityKey = 'e39b3410cf858526d5b3462db86a2ae8';

    /** @var array список шаблонов, доступных для редактирования array(key=>params, ...) */
    protected $aTemplates = array();
    /** начало макроса */
    protected $tagStart = '{';
    /** завершение макроса */
    protected $tagEnd = '}';

    /** @var string расширения шаблонов писем */
    protected $_tplExt = '.txt';
    /** @var string путь к шаблонам писем */
    protected $_tplPath = '';
    /** @var string путь к исходным шаблонам писем */
    protected $_tplPathSrc = '';

    const WRAPPER_MESSAGE_MACROS = '{message}';

    public function init()
    {
        parent::init();

        $this->module_title = 'Работа с почтой';
        $this->_tplPath = PATH_BASE . 'files' . DS . 'mail' . DS;
        $this->_tplPathSrc = $this->_tplPath . 'src' . DS;
    }

    /**
     * @return Sendmail
     */
    public static function i()
    {
        return bff::module('sendmail');
    }

    /**
     * @return SendmailModel
     */
    public static function model()
    {
        return bff::model('sendmail');
    }

    /**
     * Получение шаблона письма
     * @param string $sTemplateKey ключ шаблона
     * @param array $aTplVars набор (макрос=>значение, ...), для автозамены в шаблоне
     * @param string $lng ключ языка
     * @param boolean|null $isHTML текст письма содержит HTML теги или NULL (получить из настроек шаблона письма)
     * @param integer|bool $nWrapperID ID враппера или false (получить из настроек шаблона письма)
     * @return array (body=>string, subject=>string, is_html=>bool, wrapper_id=>int)
     */
    public function getMailTemplate($sTemplateKey, $aTplVars = false, $lng = LNG, $isHTML = null, $nWrapperID = false)
    {
        $aTemplateData = $this->getMailTemplateFromFile($sTemplateKey);
        $isHTML = $aTemplateData['is_html'] = (is_null($isHTML) ? !empty($aTemplateData['is_html']) : $isHTML);
        $nWrapperID = $aTemplateData['wrapper_id'] = (!empty($nWrapperID) ? intval($nWrapperID) : (
            !empty($aTemplateData['wrapper_id']) ? intval($aTemplateData['wrapper_id']) : 0
        ));

        if ($aTplVars !== false) {
            $aTplVars = array_merge($aTplVars, array(
                'siteurl' => SITEURL,
                'site.title' => config::sys('site.title', ''),
                'site.host' => config::sys('site.host', ''),
            ));
            $aReplace = array();
            foreach ($aTplVars as $key => $value) {
                $aReplace[$this->tagStart . $key . $this->tagEnd] = $value;
            }

            if ($isHTML) {
                $aTemplateData['body'] = strtr($aTemplateData['body'][$lng], $aReplace);
            } else {
                $aTemplateData['body'] = strtr(nl2br($aTemplateData['body'][$lng]), $aReplace);
            }
            if ($nWrapperID > 0) {
                $aTemplateData['body'] = $this->wrapMailTemplate($nWrapperID, $aTemplateData['body'], $lng);
            }
            $aTemplateData['subject'] = strtr($aTemplateData['subject'][$lng], $aReplace);
        }

        return $aTemplateData;
    }

    /**
     * Получение шаблона письма из файла
     * @param string $sTemplateKey ключ шаблона
     * @return array (body=>string, subject=>string, is_html=>bool, wrapper_id=>int)
     */
    public function getMailTemplateFromFile($sTemplateKey)
    {
        static $cache = array();

        if (!empty($cache[$sTemplateKey])) {
            return $cache[$sTemplateKey];
        }

        $sTemplatePath = $this->_tplPath . $sTemplateKey . $this->_tplExt;

        # получаем шаблон письма
        if (file_exists($sTemplatePath)) {
            $sContent = Files::getFileContent($sTemplatePath);
        } else {
            $sContent = '';
        }
        if (!$sContent) {
            # нигде не нашли, восстанавливаем
            if ($this->restoreMailTemplateFile($sTemplateKey) !== false) {
                $sContent = Files::getFileContent($sTemplatePath);
            }
        }

        $sContentUn = (!empty($sContent) ? @unserialize($sContent) : false);
        if (is_array($sContentUn)) # un-сериализация прошла успешно
        {
            $sContent = $sContentUn;
        } else if (!empty($sContent) && mb_stripos($sContent, ':{')!==false) {
            # пытаемся починить сериализованные данные
            $sContent = strtr($sContent, array("\r\n"=>"\n","\n"=>"\r\n")); // LF (Unix) => CRLF (Windows)
            $sContentUn = @unserialize($sContent);
            if (!is_array($sContentUn)) {
                $sContent = preg_replace_callback('!s:(\d+):"(.*?)";!', function($match) {
                    return ($match[1] == strlen($match[2])) ? $match[0] : 's:' . strlen($match[2]) . ':"' . $match[2] . '";';
                }, $sContent);
                $sContentUn = @unserialize($sContent);
            }
            if (is_array($sContentUn)) {
                $sContent = $sContentUn;
            }
        }

        # сохраняем в кеш и возвращаем шаблон
        if (!is_array($sContent)) {
            $sContent = array('body' => array(), 'subject' => array(), 'is_html' => false, 'wrapper_id' => 0);
            foreach ($this->locale->getLanguages() as $lng) {
                $sContent['body'][$lng] = $sContent['subject'][$lng] = '';
            }
        } else {
            foreach ($this->locale->getLanguages() as $lng) {
                foreach ($sContent as $k => $v) {
                    if (is_array($v)) {
                        if (!isset($sContent[$k][$lng])) {
                            $sContent[$k][$lng] = '';
                        }
                    }
                }
            }
        }

        return ($cache[$sTemplateKey] = $sContent);
    }

    /**
     * Восстановление шаблона письма из файла (доступно главному админу)
     * @param string $sTemplateKey ключ шаблона
     * @param int(octal) $chmod chmod
     */
    public function restoreMailTemplateFile($sTemplateKey, $chmod = 0775)
    {
        $sFilename = $sTemplateKey . $this->_tplExt;
        if (file_exists($this->_tplPathSrc . $sFilename) && is_dir($this->_tplPath)) {
            $bResult = copy($this->_tplPathSrc . $sFilename, $this->_tplPath . $sFilename);
            if ($chmod !== false) {
                @chmod($this->_tplPath . $sFilename, $chmod);
            }

            return $bResult;
        }

        return false;
    }

    /**
     * Сохранение шаблона письма в файл
     * @param string $sTemplateKey ключ шаблона
     * @param array $aTemplateData (body=>string, subject=>string) данные шаблона
     */
    public function saveMailTemplateToFile($sTemplateKey, $aTemplateData)
    {
        return Files::putFileContent($this->_tplPath . $sTemplateKey . $this->_tplExt, serialize($aTemplateData));
    }

    /**
     * Обворачиваем текст письма в шаблон
     * @param integer $nWrapperID
     * @param string $sTemplateBody
     * @param string $sLangKey
     * @return string
     */
    public function wrapMailTemplate($nWrapperID, $sTemplateBody, $sLangKey = LNG)
    {
        do {
            if (empty($nWrapperID) || $nWrapperID < 0) break;

            $aWrapperData = $this->model->wrapperData($nWrapperID, true);
            if (empty($aWrapperData)) break;

            $sWrapperContent = (
                isset($aWrapperData['content'][$sLangKey]) ? $aWrapperData['content'][$sLangKey] :
                (isset($aWrapperData['content'][LNG]) ? $aWrapperData['content'][LNG] : false)
            );
            if (empty($sWrapperContent)) break;

            if (empty($aWrapperData['is_html'])) {
                $sWrapperContent = nl2br($sWrapperContent);
            }

            $sTemplateBody = strtr($sWrapperContent, array(
                static::WRAPPER_MESSAGE_MACROS => $sTemplateBody
            ));

        } while (false);

        return $sTemplateBody;
    }

    /**
     * Отправка письма
     * @param string $to email получателя
     * @param string $subject тема письма
     * @param string $body текст письма
     * @param string $from email отправителя, если пустая строка - mail.noreply(config/sys)
     * @param string $fromName имя отправителя, если пустая строка - mail.fromname (config/sys)
     * @return bool
     */
    public function sendMail($to, $subject, $body, $from = '', $fromName = '')
    {
        $mailer = new CMail();

        if (!empty($from)) {
            $mailer->setFrom($from, (!empty($fromName) ? $fromName : $mailer->FromName));
        } else if (!empty($fromName)) {
            $mailer->FromName = $fromName;
        }

        $mailer->Subject = $subject;
        $mailer->MsgHTML($body);
        $mailer->AddAddress($to);

        $result = $mailer->Send();
        if ($result === false) {
            $this->errors->set($mailer->ErrorInfo, true);
        }

        return $result;
    }

    /**
     * Обработка копирования данных локализации
     */
    public function onLocaleDataCopy($from, $to)
    {
        # шаблоны писем
        foreach ($this->aTemplates as $key => $v) {
            $templateData = $this->getMailTemplateFromFile($key);
            foreach ($templateData as &$vv) {
                if (isset($vv[$from])) {
                    $vv[$to] = $vv[$from];
                }
            }
            unset($vv);
            $this->saveMailTemplateToFile($key, $templateData);
        }
    }

    /**
     * Формирование списка директорий/файлов требующих проверки на наличие прав записи
     * @return array
     */
    public function writableCheck()
    {
        return array_merge(parent::writableCheck(), array(
            $this->_tplPath => 'dir+files', # файлы шаблонов писем
        ));
    }
}