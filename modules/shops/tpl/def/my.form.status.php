<?php
/**
 * @var $this Shops
 */

switch($form_status)
{
    case 'add.success': {
        if ($status == Shops::STATUS_REQUEST) {
            echo $this->showInlineMessage(array(
                _t('shops', 'Вы успешно открыли магазин "<strong>[title]</strong>".', array('title'=>$title)),
                '<br />',
                _t('shops', 'После проверки модератором ваш магазин будет активирован.')
            ));
        } else {
            $aMessage = array(
                _t('shops', 'Вы успешно открыли магазин "<a [link]>[title]</a>"!', array(
                    'link'=>'href="'.$link.'"', 'title'=>$title,
                )),
            );
            echo $this->showInlineMessage($aMessage);
        }
    } break;
    case 'edit.moderating': {
        echo $this->showInlineMessage(array(
            _t('shops', 'Редактирование настроек магазина будет доступно<br />после активации вашего магазина модератором.')
        ));
    } break;
    case 'edit.blocked': {
        echo $this->showInlineMessage(array(
            _t('shops', 'Ваш магазин был заблокирован модератором, по следующей причине:'),
            '<br />',
            '<br />',
            '<strong>'.$blocked_reason.'</strong>'
        ));
    } break;
    case 'edit.notactive': {
        echo $this->showInlineMessage(array(
            _t('shops', 'Ваш магазин был деактивирован модератором.'),
            '<br />',
            _t('shops', 'Для выяснения причины деактивации обратитесь к администратору.'),
        ));
    } break;
}