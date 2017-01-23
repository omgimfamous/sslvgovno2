<?php

abstract class ContactsBase extends Module
{
    /** @var ContactsModel */
    var $model = null;

    # Типы контактов:
    const TYPE_SITE_ERROR = 1;
    const TYPE_TECH_SUPPORT = 2;
    const TYPE_OTHER = 4;

    public function init()
    {
        parent::init();
        $this->module_title = 'Контакты';
    }

    public function sendmailTemplates()
    {
        return array(
            'contacts_admin' => array(
                'title'       => 'Форма контактов: уведомление о новом сообщении',
                'description' => 'Уведомление, отправляемое <u>администратору</u> (' . config::sys('mail.admin') . ') после отправки сообщения через форму контактов',
                'vars'        => array('{name}' => 'Имя', '{email}' => 'Email', '{message}' => 'Сообщение')
            ,
                'impl'        => true,
                'priority'    => 100,
            ),
        );
    }

    /**
     * Формирование URL
     * @param string $key ключ
     * @param array $opts доп. параметры
     * @param boolean $dynamic динамическая ссылка
     * @return string
     */
    public static function url($key, array $opts = array(), $dynamic = false)
    {
        $baseUrl = static::urlBase(LNG, $dynamic);
        switch ($key) {
            # форма контактов
            case 'form':
                return $baseUrl . '/contact/';
                break;
        }
    }

    /**
     * Типы контактов
     * @param bool $options true - в виде HTML::options, false - в виде массива
     * @return array|string
     */
    protected function getContactTypes($options = false)
    {
        $types = array(
            self::TYPE_SITE_ERROR   => array('id'    => self::TYPE_SITE_ERROR,
                                             'title' => _t('contacts', 'Ошибка на сайте')
            ),
            self::TYPE_TECH_SUPPORT => array('id'    => self::TYPE_TECH_SUPPORT,
                                             'title' => _t('contacts', 'Технический вопрос')
            ),
            self::TYPE_OTHER        => array('id' => self::TYPE_OTHER, 'title' => _t('contacts', 'Другие вопросы')),
        );

        if ($options) {
            return HTML::selectOptions($types, 0, false, 'id', 'title');
        }

        return $types;
    }

    /**
     * Обновление счетчика новый сообщений, отправленных через форму
     * @param integer $nTypeID ID типа сообщения
     * @param integer $nIncrement
     */
    protected function updateCounter($nTypeID, $nIncrement)
    {
        config::saveCount('contacts_new', $nIncrement, true);
        config::saveCount('contacts_new_' . $nTypeID, $nIncrement, true);
    }

}