<?php

class UsersAvatar extends CImageUploader
{
    /**
     * Константы размеров
     */
    const szSmall = 's'; // small
    const szNormal = 'n'; // normal

    public function initSettings()
    {
        $this->path = bff::path('avatars', 'images');
        $this->pathTmp = bff::path('tmp', 'images');

        $this->url = bff::url('avatars', 'images');
        $this->urlTmp = bff::url('tmp', 'images');

        $this->table = TABLE_USERS;
        $this->fieldID = 'user_id';
        $this->fieldImage = 'avatar';
        $this->filenameLetters = 6;
        $this->folderByID = true;
        $this->maxSize = 2097152; # 2мб
        $this->minWidth = 100;
        $this->minHeight = 100;
        $this->maxWidth = 1000;
        $this->maxHeight = 1000;
        $this->sizes = array(
            self::szSmall  => array('width' => 35, 'height' => 35),
            self::szNormal => array('width' => 65, 'height' => 65),
        );
    }

    public static function url($nUserID, $sFilename, $sSizePrefix, $nSex = 0, $bTmp = false)
    {
        static $i;
        if (!isset($i)) {
            $i = new self();
        }
        $i->setRecordID($nUserID);

        if (empty($sFilename)) {
            return $i->url . $sSizePrefix . '.png';
        }

        return $i->getURL($sFilename, $sSizePrefix, $bTmp);
    }

    public static function urlDefault($sSizePrefix, $nSex = 0)
    {
        return self::url(0, 0, $sSizePrefix, $nSex);
    }

    /**
     * Загрузка аватара из соц. сети
     * @param integer $nSocialProviderID ID провайдера
     * @param string $sProviderAvatarURL URL аватара в соц. сети
     * @param boolean $bNewUserAccount true - вновь созданный аккаунт пользователя
     */
    public function uploadSocial($nSocialProviderID, $sProviderAvatarURL, $bNewUserAccount = true)
    {
        $this->setAssignErrors(false);
        if ($nSocialProviderID == UsersSocial::PROVIDER_ODNOKLASSNIKI && stripos($sProviderAvatarURL, 'stub_') !== false) {
            # Ссылка на аватар у Одноклассников.ру
            # пустой - http://usd12.odnoklassniki.ru/res/stub_128x96.gif
            # загруженный - http://usd5.odnoklassniki.ru/getImage?photoId=000000000000&photoType=2
        } else {
            # сбрасываем ограничение по ширине/высоте
            $this->setDimensions(0, 0, 0, 0);
            # загружаем
            $mResult = $this->uploadURL($sProviderAvatarURL, !$bNewUserAccount, true);
            if ($mResult !== false) {
                # обновляем аватар в сессии
                if (!$bNewUserAccount) {
                    $this->security->updateUserInfo(array('avatar' => $mResult['filename']));
                }
            }
        }
    }
}