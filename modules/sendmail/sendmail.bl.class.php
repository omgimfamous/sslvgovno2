<?php

abstract class SendmailBase extends SendmailModule
{
    /** @var SendmailModel */
    public $model = null;

    public function init()
    {
        parent::init();

        $this->aTemplates['sendmail_massend'] = array(
            'title'       => 'Почта: массовая рассылка писем',
            'description' => 'Уведомление, отправляемое при массовой рассылке',
            'vars'        => array('{msg}' => 'Текст письма')
        ,
            'impl'        => true,
            'priority'    => 1000,
        );
    }
    
    /**
     * Получение тегов начала и конца макроса
     * @return array
     */
    public function getTags()
    {
        return array($this->tagStart, $this->tagEnd);
    }

}