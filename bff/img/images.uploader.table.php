<?php
require_once("thumbnail.php");
/**
 * Компонент управляющий загрузкой / сохранением / удалением нескольких изображений
 *
 * Для хранения информации о загруженных изображениях
 * используется отдельная таблица {$tableImages}
 *
 * Структура таблицы записей {$tableRecords} предполагает наличие следующий обязательных столбцов:
 * id - ID записи (можно указать другое название через {$tableRecords_id})
 * imgfav - ID избранного изображения
 * imgcnt - счетчик кол-ва загруженных изображений записи
 *
 * @abstract
 * @version 0.475
 * @modified 21.apr.2014
 *
 * Пример сруктуры таблицы изображений {$tableImages}:
 *
 *
  CREATE TABLE `bff_*_images` (
    id int(11) unsigned NOT NULL AUTO_INCREMENT,
    item_id int(11) unsigned NOT NULL,
    user_id int(11) unsigned NOT NULL,
    filename varchar(15) NOT NULL default '',
    dir varchar(10) NOT NULL default '',
    srv tinyint(1) NOT NULL DEFAULT 0,
    created timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    crop varchar(100) NOT NULL default '',
    width mediumint(8) unsigned NOT NULL default 0,
    height mediumint(8) unsigned NOT NULL default 0,
    num int(11) unsigned NOT NULL default 0,
    PRIMARY KEY (id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

 */
use bff\img\Thumbnail;
abstract class CImagesUploaderTable extends CImagesUploader
{
    /** @var integer ID пользователя */
    protected $userID = 0;

    /** @var string Название таблицы для хранения данных о загруженных изображениях */
    protected $tableImages = '';
    /** @var string Название таблицы для хранения данных о записи */
    protected $tableRecords = '';
    /** @var string Название id-столбца в таблице для хранения данных о записи */
    protected $tableRecords_id = 'id';

    /** @var string Название поля для хранения ID записи (в таблице tableImages) */
    protected $fRecordID = 'item_id';

    /** @var boolean флаг, определяющий необходимость кешировать URL избранных размеров изображений */
    protected $useFav = false;

    /**
     * Размеры fav-изображений для кеширование полного URL пути к изображениям
     * Массив префиксов размеров в формате: array(sizePrefix=>fieldName, sizePrefix=>fieldName, ...)
     * @var array
     */
    protected $sizesFav = array();

    /**
     * Максимально доступное кол-во изображений у одной записи
     * 0 - неограничено
     * @var integer
     */
    protected $limit = 5;

    public function __construct($nRecordID = 0)
    {
        $this->init();
        $this->setRecordID($nRecordID);
        $this->setUserID($this->security->getUserID());
        $this->initSettings();
        if (empty($this->sizes)) {
            $this->errors->set(_t('images', 'Неуказаны требуемые размеры изображения'), true);
        } else {
            foreach ($this->sizes as $k => $v) {
                $this->sizes[$k]['quality'] = $this->quality;
            }
            $this->useFav = !empty($this->sizesFav);
        }
    }

    abstract protected function initSettings();

    public function setUserID($nUserID)
    {
        $this->userID = $nUserID;
    }

    /**
     * Сохранение загруженного изображения
     * @param array $aData данные о загруженном файле:
     *       tmpfile - путь к временному избражению,
     *       ext - расширение,
     *       width - ширина изображения,
     *       height - высота изображения
     * @return array информация об успешно загруженном файле изображения: [extension, filename, width, height, dir, srv, id] или FALSE
     */
    protected function save($aData, $b = false)
    {
        if (empty($aData) || empty($aData['tmpfile'])) {
            return false;
        }

        if (!$this->checkDimensions($aData['width'], $aData['height'])) {
            return false;
        }

        $bTmp = empty($this->recordID);
        if (!$bTmp) {
            $aRecordData = $this->loadRecordData($this->recordID);
            if (empty($aRecordData)) {
                $this->errors->set(sprintf('Неудалось получить информацию о записи #%s из таблицы %s', $this->recordID, $this->tableRecords), true);

                return false;
            }
            if ($this->limit && ((int)$aRecordData['imgcnt'] === $this->limit)) {
                $this->errors->set(sprintf('Неудалось сохранить изображение, достигнут лимит: %s', $this->limit));

                return false;
            }
        }

        $aSizes = $this->getSizes(false, $bTmp);

        $sFilename = func::generator($this->filenameLetters) . '.' . $aData['ext'];

        $oTh = new Thumbnail($aData['tmpfile'], false);

        $this->checkFolderByID();

        $aSave = array();

        $nServerID = $this->getRandServer();
        $sRandDir = $this->getDir();
        $aImage = array('filename' => $sFilename, 'dir' => $sRandDir, 'srv' => $nServerID);

        foreach ($aSizes as $prefix => $v) {
            $v['filename'] = $this->getPath($aImage, $prefix, $bTmp);

            # сохраняем оригинал
            if (!empty($v['o'])) {
                $sOriginalPath = $v['filename'];
                if ((!empty($v['width']) || !empty($v['height'])) && $oTh->getOriginalWidth() > $v['width']) {
                    # если изображение превышает максимально допустимую ширину
                    $aSave[] = $v;
                } else {
                    $isCopied = copy($aData['tmpfile'], $sOriginalPath);
                    if (!$isCopied) {
                        $this->errors->set(sprintf('Неудалось сохранить оригинал изображения "%s"', $sOriginalPath), true);

                        return false;
                    }
                }
            } else {
                if (!empty($v['vertical']) && $oTh->isVertical()) {
                    $aSave[] = array_merge($v, $v['vertical']);
                } else {
                    $aSave[] = $v;
                }
            }
        }

        if (!empty($aSave)) {
            if (!$oTh->save($aSave)) {
                $firstSave = current($aSave);
                $this->errors->set(sprintf('Неудалось сохранить изображение "%s"', $firstSave['filename']), true);

                return false;
            }
        }

        if (!$bTmp) {
            $nNum = $this->getRecordImagesMaxNum();

            $aImageData = array(
                $this->fRecordID => $this->recordID,
                'user_id'        => $this->userID,
                'filename'       => $sFilename,
                'created'        => $this->db->now(),
                'width'          => $aData['width'],
                'height'         => $aData['height'],
                'dir'            => $sRandDir,
                'srv'            => $nServerID,
                'num'            => $nNum,
            );

            $nImageID = $this->db->insert($this->tableImages, $aImageData);

            if (empty($nImageID)) {
                return false;
            }

            $aUpdate = array(
                'imgcnt = imgcnt + 1',
            );

            $bUpdateFav = ($this->useFav && (empty($aRecordData['imgfav']) || empty($aRecordData['imgcnt'])));
            if ($bUpdateFav) {
                $aUpdate['imgfav'] = $nImageID;
                $aImageData['id'] = $nImageID;
                foreach ($this->sizesFav as $sz => $field) {
                    $aUpdate[$field] = $this->getURL($aImageData, $sz, false);
                }
            }

            $this->saveRecordData($this->recordID, $aUpdate);
        }

        return array(
            'filename'  => $sFilename,
            'width'     => $aData['width'],
            'height'    => $aData['height'],
            'extension' => $aData['ext'],
            'dir'       => $sRandDir,
            'srv'       => $nServerID,
            'id'        => (isset($nImageID) ? $nImageID : func::generator(6, true))
        );
    }

    /**
     * Переносим temp-изображения в постоянную папку
     * @param string $sFieldname ключ в массиве $_POST, тип TYPE_ARRAY_STR
     * @param boolean $bEdit используем при редактировании записи
     * @param array $aParams параметры (
     *      deleteSizes => список префиксов размеров, которые следует удалить
     *    )
     * @return boolean
     */
    public function saveTmp($sFieldname = 'img', $bEdit = false, $aParams = array())
    {
        $this->checkFolderByID();

        # значения параметров по-умолчанию
        $aParams = array_merge(array(
                'deleteSizes' => array(),
            ), $aParams
        );

        $aImg = $this->input->post($sFieldname, TYPE_ARRAY_STR);

        if ($bEdit) {
            $nNum = $this->getRecordImagesMaxNum();
            $aData = $this->loadRecordData($this->recordID);
        } else {
            $nNum = 1;
            $aData = array();
        }
        if (empty($aData)) {
            $aData = array('imgfav' => 0, 'imgcnt' => 0);
        }

        $nServerID = $this->getRandServer();
        $sRandDir = $this->getDir();
        $aImage = array('filename' => '', 'dir' => $sRandDir, 'srv' => $nServerID, 'w' => 0, 'h' => 0);

        $sqlInsert = array();
        $sqlNOW = $this->db->now();

        $bResize = (!empty($this->sizesTmp) && sizeof($this->sizes) > sizeof($this->sizesTmp));
        if ($bResize) {
            $aSizesTmp = $this->getSizes(false, true);
            $aSizesSave = $this->getSizes($this->sizesTmp, false);
            $sBiggestTmpSize = end($this->sizesTmp);
            reset($this->sizesTmp);
        }

        # проверяем не превышается ли limit
        if ($this->limit && ($this->limit > ($aData['imgcnt'] + sizeof($aImg)))) {
            $nOffset = $this->limit - $aData['imgcnt'];
            $aImgRemove = array_slice($aImg, $nOffset);
            # сохраняем только, не превышая лимит
            $aImg = array_slice($aImg, 0, $nOffset);
            # остальные удаляем
            if (!empty($aImgRemove)) {
                foreach ($aImgRemove as $fn) {
                    $aImage['filename'] = $fn;
                    $this->deleteFile($aImage, true);
                }
            }
        }

        $i = 0;
        foreach ($aImg as $filename) {
            $aImage['filename'] = $filename;
            /**
             * Если tmp-файлы сохранены только в некоторых(sizesTmp) размерах,
             * дорезаем недостающие размеры и переносим всё в постоянную папку
             */
            if ($bResize) {
                $oTh = new Thumbnail($this->getPath($aImage, $sBiggestTmpSize, true), true);
                $aImage['w'] = $oTh->getOriginalWidth();
                $aImage['h'] = $oTh->getOriginalHeight();

                $aSave = array();
                foreach ($aSizesSave as $prefix => $v) {
                    $v['filename'] = $this->getPath($aImage, $prefix, false);
                    $aSave[] = $v;
                }

                if (!$oTh->save($aSave)) {
                    $firstSave = current($aSave);
                    $this->errors->set(sprintf('Неудалось сохранить изображение "%s"', $firstSave['filename']), true);
                    continue;
                }

                foreach ($aSizesTmp as $prefix => $v) {
                    $pathTmp = $this->getPath($aImage, $prefix, true);
                    $pathSave = $this->getPath($aImage, $prefix, false);
                    @rename($pathTmp, $pathSave);
                }
            } else {
                /**
                 * tmp-файлы уже сохранены во всех необходимых размерах,
                 * просто переносим их из временной папки в постоянную
                 */
                foreach ($this->sizes as $prefix => $v) {
                    $pathTmp = $this->getPath($aImage, $prefix, true);
                    $pathSave = $this->getPath($aImage, $prefix, false);
                    $res = @rename($pathTmp, $pathSave);
                    if (!$res) {

                    }
                }
                # последний размер - самый большой, сохраняем его размер
                if (!empty($pathSave) && file_exists($pathSave)) {
                    $imageSize = getimagesize($pathSave);
                    if (!empty($imageSize)) {
                        $aImage['w'] = $imageSize[0];
                        $aImage['h'] = $imageSize[1];
                    }
                }
            }

            $sqlInsert[] = array(
                $this->fRecordID => $this->recordID,
                'user_id'        => $this->userID,
                'filename'       => $filename,
                'created'        => $sqlNOW,
                'width'          => $aImage['w'],
                'height'         => $aImage['h'],
                'dir'            => $sRandDir,
                'srv'            => $nServerID,
                'num'            => $nNum++,
            );

            if (!empty($aParams['deleteSizes'])) {
                if (is_string($aParams['deleteSizes'])) {
                    $aParams['deleteSizes'] = array($aParams['deleteSizes']);
                }
                foreach ($aParams['deleteSizes'] as $szRemove) {
                    $path = $this->getPath($aImage, $szRemove, false);
                    if (file_exists($path)) {
                        @unlink($path);
                    }
                }
            }

            $i++;
        }

        if (!empty($sqlInsert)) {
            $res = $this->db->multiInsert($this->tableImages, $sqlInsert);
            if (empty($res)) {
                $this->errors->set(_t('uploader', 'Ошибка сохранения данных об изображениях в таблицу "[table]"', array('table' => $this->tableImages)), true);
                $i = 0;
                if (!$bEdit) {
                    # сохранить не удалось, значит и Favorite изображения тоже брать неоткуда
                    $aData['imgfav'] = 0;
                }
            } else {
                if ($this->useFav && (!$bEdit || ($bEdit && empty($aData['imgcnt'])))) {
                    $aFirstImageData = $this->db->one_array('SELECT * FROM ' . $this->tableImages . '
                                        WHERE ' . $this->fRecordID . ' = :id ORDER BY num ASC LIMIT 1',
                        array(':id' => $this->recordID)
                    );
                    if (!empty($aFirstImageData)) {
                        $this->setFavoriteImage($aFirstImageData['id'], $aFirstImageData);
                        unset($aData['imgfav']);
                    } else {
                        $this->setFavoriteImage(0); # прописываем default-ы
                        $aData['imgfav'] = 0;
                    }
                }
            }
        } else {
            if ($this->useFav && !$bEdit) {
                $this->setFavoriteImage(0); # прописываем default-ы
            }
        }

        $aData['imgcnt'] = ($bEdit ? $aData['imgcnt'] + $i : $i);

        $this->saveRecordData($this->recordID, $aData);

        return $aData;
    }

    /**
     * Сохранение порядка изображений
     * @param array $aImages данные об изображениях array(imageID=>Filename, ...) или array(filename, ...)
     * @param boolean $bContainsTmp - $aImages может содержать tmp (соответственно сохраняем порядок только всех не-tmp изображений)
     * @param boolean $bUpdateFav обновляем данные о Favorite изображении, исходя из измененного порядка
     * @return boolean
     */
    public function saveOrder($aImages, $bContainsTmp = true, $bUpdateFav = true)
    {
        if (empty($aImages)) {
            return false;
        }

        $aData = $this->loadRecordData($this->recordID);
        if (empty($aData)) {
            return false;
        }

        $aData['imgfav'] = intval($aData['imgfav']);
        $nNewFavID = 0;
        $sqlUpdate = array();
        $i = 1;

        if ($bContainsTmp) {
            $aSavedImages = $this->getData($aData['imgcnt'], 'filename');
            foreach ($aImages as $fn) {
                if (isset($aSavedImages[$fn])) {
                    $imageID = (int)$aSavedImages[$fn]['id'];
                    if ($i == 1 && ($aData['imgfav'] !== $imageID)) {
                        $nNewFavID = $imageID;
                    }
                    $sqlUpdate[] = "WHEN $imageID THEN $i";
                    $i++;
                }
            }
        } else {
            foreach ($aImages as $imageID => $fn) {
                $imageID = intval($imageID);
                if ($i == 1 && ($aData['imgfav'] !== $imageID)) {
                    $nNewFavID = $imageID;
                }
                $sqlUpdate[] = "WHEN $imageID THEN $i";
                $i++;
            }
        }

        if (!empty($sqlUpdate)) {
            # обновляем порядок
            $res = $this->db->exec('UPDATE ' . $this->tableImages . '
                                SET num = CASE id ' . join(' ', $sqlUpdate) . ' ELSE num END
                                WHERE ' . $this->fRecordID . ' = :id', array(':id' => $this->recordID)
            );

            if (!empty($res) && $nNewFavID != 0 && $this->useFav && $bUpdateFav) {
                $this->setFavoriteImage($nNewFavID);
            }
        }

        return true;
    }

    /**
     * Обновляем данные о Favorite изображении
     * @param integer $nImageID ID нового Favorite изображения
     * @apram mixed $aData информация об изображении $nImageID или FALSE
     */
    public function setFavoriteImage($nImageID, $aImageData = false)
    {
        $aUpdate = array(
            'imgfav' => $nImageID
        );

        if (!empty($this->sizesFav)) {
            if ($nImageID == 0) {
                foreach ($this->sizesFav as $sz => $field) {
                    $aUpdate[$field] = $this->urlDefault($sz);
                }
            } else {
                if (empty($aImageData)) {
                    $aImageData = $this->getImageData($nImageID);
                }
                if (!empty($aImageData)) {
                    foreach ($this->sizesFav as $sz => $field) {
                        $aUpdate[$field] = $this->getURL($aImageData, $sz, false);
                    }
                } else {
                    # не смогли получить данные о fav изображении
                    # обнуляем imgfav
                    $aUpdate['imgfav'] = 0;
                    foreach ($this->sizesFav as $sz => $field) {
                        $aUpdate[$field] = $this->urlDefault($sz);
                    }
                }
            }
        }

        $this->saveRecordData($this->recordID, $aUpdate);
    }

    /**
     * Удаление изображений
     * @param array $aImages данные об изображениях array( Filename, Filename, ...)
     * @return integer кол-во удаленных изображений
     */
    public function deleteImages($aImages)
    {
        if (empty($aImages)) {
            return 0;
        }
        $aData = $this->loadRecordData($this->recordID);
        if (empty($aData)) {
            return 0;
        }

        $aData['imgfav'] = intval($aData['imgfav']);

        $aDeleteImgsID = array();
        $bUpdateFav = false;
        $aSavedImages = $this->getData($aData['imgcnt'], 'filename');
        foreach ($aImages as $imageFilename) {
            if (isset($aSavedImages[$imageFilename])) {
                $aImg = $aSavedImages[$imageFilename];
                if ($aImg['user_id'] == $this->userID) {
                    $this->deleteFile($aImg);

                    $aDeleteImgsID[] = $aImg['id'];
                    if ($aData['imgfav'] == $aImg['id']) {
                        $bUpdateFav = true;
                    }

                    unset($aSavedImages[$imageFilename]);
                }
            }
        }

        if (!empty($aDeleteImgsID)) {
            $bRes = $this->db->delete($this->tableImages, array(
                    'id'             => $aDeleteImgsID,
                    $this->fRecordID => $this->recordID
                )
            );
            if (empty($bRes)) {
                return 0;
            }

            $aUpdate = array('imgcnt = imgcnt - ' . count($aDeleteImgsID));

            if ($bUpdateFav && $this->useFav) {
                if (empty($aSavedImages)) {
                    $aUpdate['imgfav'] = 0;
                    foreach ($this->sizesFav as $sz => $field) {
                        $aUpdate[$field] = $this->urlDefault($sz);
                    }
                } else {
                    $aNewFavImage = reset($aSavedImages);
                    $this->setFavoriteImage($aNewFavImage['id'], $aNewFavImage);
                }
            }

            $this->saveRecordData($this->recordID, $aUpdate);

            return $bRes;
        }

        return 0;
    }

    /**
     * Удаление изображения
     * @param integer $nImageID ID изображения
     * @param mixed $aData информация об изображении $nImageID или FALSE
     * @return boolean
     */
    public function deleteImage($nImageID, $aData = false)
    {
        $bSave = false;
        if ($aData === false) {
            $aData = $this->db->one_array('SELECT I.*, R.imgfav, R.imgcnt
                                FROM ' . $this->tableImages . ' I,
                                     ' . $this->tableRecords . ' R
                                WHERE I.' . $this->fRecordID . ' = :id
                                  AND I.id = :imageid
                                  AND I.' . $this->fRecordID . ' = R.' . $this->tableRecords_id,
                array(':id' => $this->recordID, ':imageid' => $nImageID)
            );

            if (empty($aData)) {
                return false;
            }

            $bSave = true;
        }

        $res = $this->db->delete($this->tableImages, array('id' => $nImageID, $this->fRecordID => $this->recordID));
        if (empty($res)) {
            return false;
        }

        $this->deleteFile($aData);

        if ($bSave) {
            $aUpdate = array('imgcnt = imgcnt - 1');

            /**
             * Если удаляем Favorite изображение
             */
            if ($this->useFav && intval($aData['imgfav']) === $nImageID) {
                if ($aData['imgcnt'] > 1) {
                    # Назначаем следующее (с минимальным num)
                    $aNextImageData = $this->db->one_array('SELECT * FROM ' . $this->tableImages . '
                                                        WHERE ' . $this->fRecordID . ' = :id
                                                        ORDER BY num LIMIT 1', array(':id' => $this->recordID)
                    );
                    if (!empty($aNextImageData)) {
                        $this->setFavoriteImage($aNextImageData['id'], $aNextImageData);
                    } else {
                        # ?
                    }
                } else {
                    $aUpdate['imgfav'] = 0;
                    foreach ($this->sizesFav as $sz => $field) {
                        $aUpdate[$field] = $this->urlDefault($sz);
                    }
                }
            }

            # выполняем именно "imgcnt = imgcnt - 1", для пущей уверенности
            $this->saveRecordData($this->recordID, $aUpdate);
        }

        return true;
    }

    /**
     * Удаление всех изображений связанных с записью
     * @param boolean $bUpdateQuery актуализировать ли данные о изображениях записи (после их удаления)
     * @return boolean
     */
    public function deleteAllImages($bUpdateQuery = false)
    {
        $aImages = $this->db->select('SELECT * FROM ' . $this->tableImages . ' WHERE ' . $this->fRecordID . ' = :id',
            array(':id' => $this->recordID)
        );
        if (empty($aImages)) {
            return false; # нечего удалять
        }

        foreach ($aImages as $v) {
            $this->deleteFile($v, false);
        }

        $this->db->delete($this->tableImages, array($this->fRecordID => $this->recordID));

        if ($bUpdateQuery) {
            $sqlUpdate = array('imgcnt' => 0);
            if ($this->useFav) {
                $sqlUpdate['imgfav'] = 0;
                foreach ($this->sizesFav as $sz => $field) {
                    $sqlUpdate[$field] = $this->urlDefault($sz);
                }
            }
            $this->saveRecordData($this->recordID, $sqlUpdate);
        }

        return true;
    }

    /**
     * Формирование default URL для требуемого размера
     * @param $sSizePrefix
     * @return string URL
     */
    abstract function urlDefault($sSizePrefix);

    /**
     * Получаем данные о записи
     * @param integer $nRecordID ID записи
     * @return array
     */
    protected function loadRecordData($nRecordID)
    {
        return $this->db->one_array('SELECT ' . $this->tableRecords_id . ', imgfav, imgcnt
                    FROM ' . $this->tableRecords . '
                    WHERE ' . $this->tableRecords_id . ' = :id',
            array(':id' => $nRecordID)
        );
    }

    /**
     * Сохраняем данные о записи
     * @param integer $nRecordID ID записи
     * @param array $aRecordData данные
     * @return mixed
     */
    protected function saveRecordData($nRecordID, array $aRecordData)
    {
        return $this->db->update($this->tableRecords, $aRecordData, array(
                $this->tableRecords_id => $nRecordID
            )
        );
    }

    /**
     * Получаем наибольший num текущих изображений записи + 1
     * @return integer Num
     */
    protected function getRecordImagesMaxNum()
    {
        return ((int)$this->db->one_data('SELECT MAX(num) FROM ' . $this->tableImages . '
                    WHERE ' . $this->fRecordID . ' = :id', array(':id' => $this->recordID)
        )) + 1;
    }

    /**
     * Получаем данные о загруженных и сохраненных на текущий момент изображениях
     * @param mixed $nCount кол-во изображений, false - если не знаем
     * @param string $sKey по какому полю строить ключи в результате
     * @return array данные о изображениях или FALSE
     */
    public function getData($nCount = false, $sKey = 'id')
    {
        if (($nCount !== false && intval($nCount) === 0) || $this->recordID === 0) {
            return array();
        }

        return $this->db->select_key('SELECT * FROM ' . $this->tableImages . '
                WHERE ' . $this->fRecordID . ' = :id ORDER BY num', $sKey,
            array(':id' => $this->recordID)
        );
    }

    /**
     * Получаем данные о загруженном и сохраненном изображении
     * @param integer $nImageID ID изображения
     * @return array массив параметров изображения
     */
    public function getImageData($nImageID)
    {
        if (empty($nImageID)) {
            return array();
        }

        return $this->db->one_array('SELECT * FROM ' . $this->tableImages . '
                    WHERE id = :imageID
                      AND ' . $this->fRecordID . ' = :recordID
                    LIMIT 1', array(':imageID' => $nImageID, ':recordID' => $this->recordID)
        );
    }

    /**
     * Получаем данные о загруженных и сохраненных изображений
     * @param array|integer $aImageID ID изображений
     * @return array данные о изображениях: array(imageID=>Data, ...)
     */
    public function getImagesData($aImageID)
    {
        if (empty($aImageID)) {
            return array();
        }
        if (!is_array($aImageID)) {
            $aImageID = array($aImageID);
        }

        return $this->db->select_key('SELECT * FROM ' . $this->tableImages . '
                    WHERE id IN (' . join(',', $aImageID) . ') AND
                          ' . $this->fRecordID . ' = :id', 'id', array(':id' => $this->recordID)
        );

    }

    /**
     * Просмотр галереи изображений
     * @param integer $nImageID ID текущего изображения
     * @param integer $nOffset отступ
     * @param integer $nDir направление (0 - первый показ, 1 - назад, -1 - вперед)
     * @param integer $nTotal общее кол-во изображений
     * @return array [offset=>integer,total=>integer,images=>array] или []
     */
    public function processImagesGallery($nImageID, $nOffset, $nDir, $nTotal) // niu
    {
        do {
            if (!$this->recordID) {
                break;
            }

            $sql = array(
                $this->fRecordID . ' = ' . $this->recordID
            );

            $sql = join(' AND ', $sql);

            if (!$nDir) # первый показ
            {
                $sNumField = 'num';

                if (!$nImageID) {
                    break;
                }

                $aImage = $this->db->one_array('SELECT * FROM ' . $this->tableImages . '
                        WHERE id = :imageid AND ' . $sql, array(':imageid' => $nImageID)
                );
                if (empty($aImage)) {
                    break;
                }

                $i = $aImage[$sNumField];

                if ($nTotal <= 10) {
                    $from = 1;
                    $to = $nTotal;
                    $nOffset = 0;
                } else {
                    $from = $i - 3;
                    if ($from < 0) {
                        $from = $nTotal - abs($from);
                    }
                    $to = $i + 6;
                    if ($to > $nTotal) {
                        $to = $to - $nTotal;
                    }
                    $nOffset = $from;
                }
                $aImages = $this->db->select('SELECT * FROM ' . $this->tableImages . '
                    WHERE id != ' . $nImageID . '
                      AND (' . ($from > $to ?
                        $sNumField . '>=' . $from . ' OR ' . $sNumField . '<=' . $to :
                        $sNumField . '>=' . $from . ' OR ' . $sNumField . '<=' . $to) . ')
                      AND ' . $sql
                );
                $aImages[] = $aImage; # добавляем текущую
            } else {
                $i = $nOffset;
                if ($nDir == 1) { # +10
                    $from = $nOffset;
                    $to = $i + 10;
                    if ($to > $nTotal) {
                        $to = $to - $nTotal;
                    }
                } else { # -10
                    $from = $i - 10;
                    if ($from < 0) {
                        $from = $nTotal - abs($from);
                    }
                    $to = $nOffset;
                }
                $aImages = $this->db->select('SELECT * FROM ' . $this->tableImages . '
                    WHERE (' . ($from > $to ?
                        $sNumField . '>=' . $from . ' OR ' . $sNumField . '<=' . $to :
                        $sNumField . '>=' . $from . ' OR ' . $sNumField . '<=' . $to) . ')
                      AND ' . $sql
                );
            }

            return array(
                'offset' => $nOffset,
                'total'  => $nTotal,
                'images' => $aImages,
            );
        } while (false);

        return array();
    }

    /**
     * Получение максимально доступного кол-ва изображений у одной записи
     * @return integer
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Установка максимально доступного кол-ва изображений у одной записи
     * @param $nLimit integer
     */
    public function setLimit($nLimit)
    {
        return ($this->limit = $nLimit);
    }

}