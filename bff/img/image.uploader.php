<?php 

require_once("thumbnail.php");
abstract class CImageUploader extends Component
{
    protected $recordID = 0;
    protected $table = "";
    protected $tableSkip = false;
    protected $fieldID = "id";
    protected $fieldImage = "img";
    protected $fieldCrop = "img_crop";
    protected $path = "";
    protected $pathTmp = "";
    protected $url = "";
    protected $urlTmp = "";
    protected $assignErrors = true;
    protected $maxSize = 4194304;
    protected $minWidth = 0;
    protected $minHeight = 0;
    protected $maxWidth = 0;
    protected $maxHeight = 0;
    protected $sizes = array(  );
    protected $sizesTmp = array(  );
    protected $filenameLetters = 6;
    protected $extensionsAllowed = array( "jpg", "jpeg", "gif", "png" );
    protected $quality = 90;
    protected $folderByID = false;

    public function __construct($e04b532537e6d779 = 0)
    {
        $this->init();
        $this->setRecordID($e04b532537e6d779);
        $this->initSettings();
        if( empty($this->sizes) ) 
        {
            $this->errors->set("Неуказаны требуемые размеры изображения", true);
        }
        else
        {
            foreach( $this->sizes as $C5ce6166bbc26b8 => $e8b0c110048 ) 
            {
                $this->sizes[$C5ce6166bbc26b8]["quality"] = $this->quality;
            }
        }

    }

    public function setRecordID($G32ec6f80999d29)
    {
        $this->recordID = $G32ec6f80999d29;
    }

    abstract protected function initSettings();

    public function uploadFILES($sInput, $bDeletePrevious = true, $bDoUpdateQuery = false)
    {
        $aResult = false;
        $sFilenameTmp = false;
        do
        {
            if(empty($_FILES[$sInput]) || $_FILES[$sInput]['error']==UPLOAD_ERR_NO_FILE)
                break;

            $oUpload = new CUploader($sInput, false);
            if( ! $this->errors->no()) {
                break;
            }

            $sFilenameTmp = $oUpload->getFilenameUploaded();
            # проверяем размер файла
            if( ! $oUpload->checkSize( $this->maxSize ))
                break;

            # проверяем расширение файла
            $aImageSize = getimagesize( $sFilenameTmp );
            $sExtension = bff\utils\Files::getExtension( $oUpload->getFilename() );
            if($aImageSize === FALSE
                || ( !empty($this->extensionsAllowed) && !in_array($sExtension, $this->extensionsAllowed) )
                || !in_array($aImageSize[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG))
            ) {
                $this->errors->setUploadError(CUploader::errWrongType);
                break;
            }

            if( ! $this->checkDimensions($aImageSize[0], $aImageSize[1])) {
                break;
            }

            # сохраняем изображение
            $sFilename = $this->save(array(
                'ext'     => $sExtension,
                'tmpfile' => $sFilenameTmp,
            ), $bDeletePrevious, $bDoUpdateQuery);

            if($sFilename === false) {
                break;
            }

            $aResult = array(
                'filename'  => $sFilename,
                'width'     => $aImageSize[0],
                'height'    => $aImageSize[1],
                'extension' => $sExtension,
            );

        } while(false);

        $this->deleteTmpUploadedFile($sFilenameTmp);

        return $aResult;
    }

    public function uploadQQ($bDeletePrevious = true, $bDoUpdateQuery = false)
    {
        include_once PATH_CORE.'external/qquploader.php';

        $oUploadQQ = new qqFileUploader($this->extensionsAllowed, $this->maxSize); // qqfile
        $sExtension = $oUploadQQ->getFilenameExtension();

        $aResult = false;

        # генерируем имя временного файла
        $sFilenameTmp = $this->buildTmpUploadFilename($sExtension);

        do
        {
            if( $oUploadQQ->upload($sFilenameTmp) !== true ) {
                break;
            }

            $aImageSize = getimagesize( $sFilenameTmp );

            if( ! $this->checkDimensions($aImageSize[0], $aImageSize[1])) {
                break;
            }

            # сохраняем изображение
            $sFilename = $this->save(array(
                    'ext'     => $sExtension,
                    'tmpfile' => $sFilenameTmp,
                ), $bDeletePrevious, $bDoUpdateQuery);

            if($sFilename === false) break;

            $aResult = array(
                'filename'  => $sFilename,
                'width'     => $aImageSize[0],
                'height'    => $aImageSize[1],
                'extension' => $sExtension,
            );

        } while(false);

        $this->deleteTmpUploadedFile( $sFilenameTmp );

        return $aResult;
    }

    public function uploadSWF($Q7708157125a31e5960 = true, $fd02e6dc8ed5d = false)
    {
        $A0c027a94ce454 = false;
        $A0c027a94ce455 = CUploader::swfuploadStart(true, $this->extensionsAllowed);
        if( is_string($A0c027a94ce455) ) 
        {
            if( $this->assignErrors ) 
            {
                $this->errors->set($A0c027a94ce455);
            }

            break;
        }

        if( !$this->checkDimensions($A0c027a94ce455["width"], $A0c027a94ce455["height"]) ) 
        {
            break;
        }

        $A0c027a94ce456 = $this->save(array( "ext" => $A0c027a94ce455["ext"], "tmpfile" => $A0c027a94ce455["tmp_name"] ), $Q7708157125a31e5960, $fd02e6dc8ed5d);
        $this->deleteTmpUploadedFile($A0c027a94ce455["tmp_name"]);
        if( $A0c027a94ce456 === false ) 
        {
            break;
        }

        $A0c027a94ce454 = array( "filename" => $A0c027a94ce456, "width" => $A0c027a94ce455["width"], "height" => $A0c027a94ce455["height"], "extension" => $A0c027a94ce455["ext"] );
        if( !false ) 
        {
            return $A0c027a94ce454;
        }

    }

    public function uploadURL($H42fddd8, $V2083da2382ea4 = true, $ce4044de613b1 = false)
    {
        $A0c027a94ce457 = false;
        $A0c027a94ce458 = false;
        while( empty($H42fddd8) ) 
        {
            break;
        }
        $A0c027a94ce459 = function_exists("getimagesizefromstring");
        if( !$A0c027a94ce459 ) 
        {
            $A0c027a94ce460 = getimagesize($H42fddd8);
            if( empty($A0c027a94ce460) || empty($A0c027a94ce460[0]) && empty($A0c027a94ce460[1]) || empty($A0c027a94ce460[2]) ) 
            {
                if( $this->assignErrors ) 
                {
                    $this->errors->setUploadError(Errors::FILE_UPLOAD_ERROR);
                }

                break;
            }

            $A0c027a94ce461 = image_type_to_extension($A0c027a94ce460[2], false);
            if( !in_array($A0c027a94ce461, $this->extensionsAllowed) ) 
            {
                if( $this->assignErrors ) 
                {
                    $this->errors->setUploadError(Errors::FILE_WRONG_TYPE);
                }

                break;
            }

            if( !$this->checkDimensions($A0c027a94ce460[0], $A0c027a94ce460[1]) ) 
            {
                break;
            }

        }

        $A0c027a94ce458 = $this->buildTmpUploadFilename("tmp");
        $A0c027a94ce462 = file_get_contents($H42fddd8);
        if( empty($A0c027a94ce462) ) 
        {
            if( $this->assignErrors ) 
            {
                $this->errors->setUploadError(Errors::FILE_UPLOAD_ERROR);
            }

            break;
        }

        if( $A0c027a94ce459 ) 
        {
            $A0c027a94ce460 = getimagesizefromstring($A0c027a94ce462);
            if( empty($A0c027a94ce460) || empty($A0c027a94ce460[0]) && empty($A0c027a94ce460[1]) || empty($A0c027a94ce460[2]) ) 
            {
                if( $this->assignErrors ) 
                {
                    $this->errors->setUploadError(Errors::FILE_UPLOAD_ERROR);
                }

                break;
            }

            $A0c027a94ce461 = image_type_to_extension($A0c027a94ce460[2], false);
            if( !in_array($A0c027a94ce461, $this->extensionsAllowed) ) 
            {
                if( $this->assignErrors ) 
                {
                    $this->errors->setUploadError(Errors::FILE_WRONG_TYPE);
                }

                break;
            }

            if( !$this->checkDimensions($A0c027a94ce460[0], $A0c027a94ce460[1]) ) 
            {
                break;
            }

        }

        $A0c027a94ce463 = file_put_contents($A0c027a94ce458, $A0c027a94ce462, LOCK_EX);
        if( $A0c027a94ce463 === false ) 
        {
            if( $this->assignErrors ) 
            {
                $this->errors->set("Неудалось сохранить файл во временную папку", true);
                $this->errors->setUploadError(Errors::FILE_UPLOAD_ERROR);
            }

            break;
        }

        $A0c027a94ce464 = filesize($A0c027a94ce458);
        if( !$this->checkSize($A0c027a94ce464) ) 
        {
            break;
        }

        $A0c027a94ce465 = $this->save(array( "ext" => $A0c027a94ce461, "tmpfile" => $A0c027a94ce458 ), $V2083da2382ea4, $ce4044de613b1);
        if( $A0c027a94ce465 === false ) 
        {
            break;
        }

        $A0c027a94ce457 = array( "filename" => $A0c027a94ce465, "width" => $A0c027a94ce460[0], "height" => $A0c027a94ce460[1], "extension" => $A0c027a94ce461 );
        if( !false ) 
        {
            $this->deleteTmpUploadedFile($A0c027a94ce458);
            return $A0c027a94ce457;
        }

    }

    protected function save($Y3db4aa279302b3c0a, $Ee5bb748295b6 = true, $Sa646b965ffb0d5 = false)
    {
        if( empty($Y3db4aa279302b3c0a) || empty($Y3db4aa279302b3c0a["tmpfile"]) ) 
        {
            return false;
        }

        $A0c027a94ce466 = empty($this->recordID);
        $A0c027a94ce467 = $this->getSizes(false, $A0c027a94ce466);
        $A0c027a94ce468 = func::generator($this->filenameLetters) . "." . $Y3db4aa279302b3c0a["ext"];
        $A0c027a94ce469 = new bff\img\Thumbnail($Y3db4aa279302b3c0a["tmpfile"], false);
        $this->checkFolderByID();
        $A0c027a94ce470 = array(  );
        foreach( $A0c027a94ce467 as $A0c027a94ce471 => $A0c027a94ce472 ) 
        {
            $A0c027a94ce472["filename"] = $this->getPath($A0c027a94ce468, $A0c027a94ce471, $A0c027a94ce466);
            if( !empty($A0c027a94ce472["o"]) ) 
            {
                if( !empty($A0c027a94ce472["width"]) && $A0c027a94ce472["width"] < $A0c027a94ce469->getOriginalWidth() || !empty($A0c027a94ce472["height"]) && $A0c027a94ce472["height"] < $A0c027a94ce469->getOriginalHeight() ) 
                {
                    $A0c027a94ce470[] = $A0c027a94ce472;
                }
                else
                {
                    $A0c027a94ce473 = copy($Y3db4aa279302b3c0a["tmpfile"], $A0c027a94ce472["filename"]);
                    if( $A0c027a94ce473 === false ) 
                    {
                        if( $this->assignErrors ) 
                        {
                            $this->errors->set("Неудалось сохранить оригинал изображение", true);
                        }

                        return false;
                    }

                }

            }
            else
            {
                $A0c027a94ce470[] = $A0c027a94ce472;
            }

        }
        if( !empty($A0c027a94ce470) && !$A0c027a94ce469->save($A0c027a94ce470) ) 
        {
            if( $this->assignErrors ) 
            {
                $this->errors->set(_t("img", "Неудалось сохранить изображение"));
            }

            return false;
        }

        if( !$A0c027a94ce466 ) 
        {
            if( $Ee5bb748295b6 ) 
            {
                $this->delete(false);
            }

            if( $Sa646b965ffb0d5 ) 
            {
                $this->saveToDatabase($A0c027a94ce468);
            }

        }

        return $A0c027a94ce468;
    }

    public function untemp($ebff6db, $db4c3268 = false)
    {
        $this->checkFolderByID();
        if( !empty($this->sizesTmp) && sizeof($this->sizesTmp) < sizeof($this->sizes) ) 
        {
            $A0c027a94ce474 = $this->getSizes(false, true);
            $A0c027a94ce475 = $this->getSizes($this->sizesTmp, false);
            $A0c027a94ce476 = end($this->sizesTmp);
            reset($this->sizesTmp);
            $A0c027a94ce477 = new bff\img\Thumbnail($this->getPath($ebff6db, $A0c027a94ce476, true), true);
            $A0c027a94ce478 = array(  );
            foreach( $A0c027a94ce475 as $A0c027a94ce479 => $A0c027a94ce480 ) 
            {
                $A0c027a94ce480["filename"] = $this->getPath($ebff6db, $A0c027a94ce479, false);
                $A0c027a94ce478[] = $A0c027a94ce480;
            }
            if( !$A0c027a94ce477->save($A0c027a94ce478) ) 
            {
                if( $this->assignErrors ) 
                {
                    $this->errors->set(_t("img", "Неудалось сохранить изображение"));
                }

                return false;
            }

            foreach( $A0c027a94ce474 as $A0c027a94ce479 => $A0c027a94ce480 ) 
            {
                $pathTmp = $this->getPath($ebff6db, $A0c027a94ce479, true);
                $A0c027a94ce481 = $this->getPath($ebff6db, $A0c027a94ce479, false);
                @rename($pathTmp, $A0c027a94ce481);
            }
        }
        else
        {
            $A0c027a94ce482 = 0;
            foreach( $this->sizes as $A0c027a94ce479 => $A0c027a94ce480 ) 
            {
                $pathTmp = $this->getPath($ebff6db, $A0c027a94ce479, true);
                $A0c027a94ce481 = $this->getPath($ebff6db, $A0c027a94ce479, false);
                if( !$A0c027a94ce482++ && !file_exists($pathTmp) ) 
                {
                    return false;
                }

                @rename($pathTmp, $A0c027a94ce481);
            }
        }

        if( $db4c3268 ) 
        {
            $this->saveToDatabase($ebff6db);
        }

    }

    public function crop($ceb6434, $Rb4ce756e9d2f83ee9, $d0253b16a68, $V903de7a1b22, $sa3933b6 = false)
    {
        $A0c027a94ce483 = $this->prepareCropParams($V903de7a1b22);
        if( empty($A0c027a94ce483) ) 
        {
            return false;
        }

        $A0c027a94ce484 = $this->getPath($ceb6434, $Rb4ce756e9d2f83ee9, $sa3933b6);
        if( !file_exists($A0c027a94ce484) ) 
        {
            if( $this->assignErrors ) 
            {
                $this->errors->set(sprintf("Исходное изображение отсутствует \"%s\"", $A0c027a94ce484), true);
            }

            return false;
        }

        $A0c027a94ce485 = new bff\img\Thumbnail($A0c027a94ce484, true);
        $A0c027a94ce486 = array(  );
        if( empty($d0253b16a68) ) 
        {
            $d0253b16a68 = array( $Rb4ce756e9d2f83ee9 );
        }

        $A0c027a94ce487 = $this->getSizes($d0253b16a68, $sa3933b6);
        foreach( $A0c027a94ce487 as $A0c027a94ce488 => $A0c027a94ce489 ) 
        {
            if( !empty($A0c027a94ce489["o"]) ) 
            {
                continue;
            }

            $A0c027a94ce489["filename"] = $this->getPath($ceb6434, $A0c027a94ce488, $sa3933b6);
            $A0c027a94ce489["src_x"] = $A0c027a94ce483["x"];
            $A0c027a94ce489["src_y"] = $A0c027a94ce483["y"];
            $A0c027a94ce489["crop_width"] = $A0c027a94ce483["w"];
            $A0c027a94ce489["crop_height"] = $A0c027a94ce483["h"];
            $A0c027a94ce489["autofit"] = false;
            $A0c027a94ce486[] = $A0c027a94ce489;
        }
        if( !$A0c027a94ce485->save($A0c027a94ce486) ) 
        {
            return false;
        }

        $this->saveToDatabase($ceb6434, $V903de7a1b22);
        return true;
    }

    protected function saveToDatabase($e346df114fb85, $X87f1668aeacf08 = false)
    {
        if( $this->tableSkip ) 
        {
            return 1;
        }

        $A0c027a94ce490 = array( $this->fieldImage => $e346df114fb85 );
        if( $X87f1668aeacf08 !== false ) 
        {
            $A0c027a94ce490[$this->fieldCrop] = $X87f1668aeacf08;
        }

        return $this->db->update($this->table, $A0c027a94ce490, array( $this->fieldID => $this->recordID ));
    }

   public function delete($bUpdateRecord = true, $sFilename = false)
    {
        if(empty($sFilename)) {
            $sFilename = $this->db->one_data("SELECT $this->fieldImage
                             FROM $this->table WHERE $this->fieldID = $this->recordID");
        }
        if(empty($sFilename) || ! $this->recordID) return false;

        $this->deleteFile($sFilename, false);

        if($bUpdateRecord) {
            $this->saveToDatabase('');
        }
        return true;
    }

    public function deleteTmp($Ld4cc1ac)
    {
        return $this->deleteFile($Ld4cc1ac, true);
    }

    protected function deleteFile($I0c3d2a, $deec816faf59 = false)
    {
        if( empty($I0c3d2a) ) 
        {
            return false;
        }

        foreach( $this->getSizes(false, $deec816faf59) as $A0c027a94ce491 => $A0c027a94ce492 ) 
        {
            $path = $this->getPath($I0c3d2a, $A0c027a94ce491, $deec816faf59);
            if( file_exists($path) ) 
            {
                @unlink($path);
            }

        }
        return true;
    }

    protected function buildTmpUploadFilename($Qa000adf)
    {
        return $this->pathTmp . $this->recordID . "tmp" . func::generator($this->filenameLetters) . "." . $Qa000adf;
    }

    protected function deleteTmpUploadedFile($u26719)
    {
        if( !empty($u26719) && file_exists($u26719) && is_writable(pathinfo($u26719, PATHINFO_DIRNAME)) ) 
        {
            return @unlink($u26719);
        }

        return false;
    }

    protected function checkDimensions($Da3cfeab33a3de97, $na159cfba58ad1874a)
    {
        if( 0 < $this->minWidth && $Da3cfeab33a3de97 < $this->minWidth ) 
        {
            if( $this->assignErrors ) 
            {
                $this->errors->set(_t("img", "Изображение меньше допустимой ширины [width]px", array( "width" => $this->minWidth )));
            }

            return false;
        }

        if( 0 < $this->maxWidth && $this->maxWidth < $Da3cfeab33a3de97 ) 
        {
            if( $this->assignErrors ) 
            {
                $this->errors->set(_t("img", "Изображение больше допустимой ширины [width]px", array( "width" => $this->maxWidth )));
            }

            return false;
        }

        if( 0 < $this->minHeight && $na159cfba58ad1874a < $this->minHeight ) 
        {
            if( $this->assignErrors ) 
            {
                $this->errors->set(_t("img", "Изображение меньше допустимой высоты [height]px", array( "height" => $this->minHeight )));
            }

            return false;
        }

        if( 0 < $this->maxHeight && $this->maxHeight < $na159cfba58ad1874a ) 
        {
            if( $this->assignErrors ) 
            {
                $this->errors->set(_t("img", "Изображение больше допустимой высоты [height]px", array( "height" => $this->maxHeight )));
            }

            return false;
        }

        return true;
    }

    public function setDimensions($d53b6f = false, $mb736b7b64b7 = false, $c45203bf0 = false, $c57b3cd12ed25d2d = false)
    {
        if( $d53b6f !== false ) 
        {
            $this->minWidth = $d53b6f;
        }

        if( $mb736b7b64b7 !== false ) 
        {
            $this->maxWidth = $mb736b7b64b7;
        }

        if( $c45203bf0 !== false ) 
        {
            $this->minHeight = $c45203bf0;
        }

        if( $c57b3cd12ed25d2d !== false ) 
        {
            $this->maxHeight = $c57b3cd12ed25d2d;
        }

    }

    public function setAssignErrors($l2e9d389ede)
    {
        $this->assignErrors = $l2e9d389ede;
    }

    protected function checkSize($Cadbb78dfcdc291a1)
    {
        if( !$Cadbb78dfcdc291a1 ) 
        {
            if( $this->assignErrors ) 
            {
                $this->errors->setUploadError(Errors::FILE_WRONG_SIZE);
            }

            return false;
        }

        if( 0 < $this->maxSize && $this->maxSize < $Cadbb78dfcdc291a1 ) 
        {
            if( $this->assignErrors ) 
            {
                $this->errors->setUploadError(Errors::FILE_MAX_SIZE, array( "size" => tpl::filesize($this->maxSize) ));
            }

            return false;
        }

        return true;
    }

    protected function checkFolderByID()
    {
        if( $this->folderByID && !empty($this->recordID) && 950 < $this->recordID - intval($this->recordID / 1000) * 1000 ) 
        {
            $A0c027a94ce493 = $this->path . intval(($this->recordID + 100) / 1000);
            if( !file_exists($A0c027a94ce493) ) 
            {
                bff\utils\Files::makeDir($A0c027a94ce493);
            }

        }

    }

    protected function getPath($b98ab67f0740, $Cb0e2b0cff4ae, $f28bf93b1753 = false)
    {
        return ($f28bf93b1753 ? $this->pathTmp . "0" : $this->path . ($this->folderByID ? intval($this->recordID / 1000) . DS : "") . $this->recordID) . $Cb0e2b0cff4ae . $b98ab67f0740;
    }

    public function getURL($b68624c7c, $b5cf06564bad34f, $c259763cc2 = false)
    {
        if( is_array($b5cf06564bad34f) ) 
        {
            $A0c027a94ce494 = array(  );
            foreach( $b5cf06564bad34f as $A0c027a94ce495 ) 
            {
                $A0c027a94ce494[$A0c027a94ce495] = $this->getURL($b68624c7c, $A0c027a94ce495, $c259763cc2);
            }
            return $A0c027a94ce494;
        }

        return ($c259763cc2 ? $this->urlTmp . "0" : $this->url . ($this->folderByID ? intval($this->recordID / 1000) . "/" : "") . $this->recordID) . $b5cf06564bad34f . $b68624c7c;
    }

    protected function getSizes($f70c865 = array(  ), $d080bca1e = false)
    {
        $A0c027a94ce496 = $this->sizes;
        if( !empty($f70c865) ) 
        {
            if( !is_array($f70c865) ) 
            {
                $f70c865 = array( $f70c865 );
            }

            foreach( $f70c865 as $A0c027a94ce497 ) 
            {
                if( isset($A0c027a94ce496[$A0c027a94ce497]) ) 
                {
                    unset($A0c027a94ce496[$A0c027a94ce497]);
                }

            }
        }

        if( $d080bca1e === true && !empty($this->sizesTmp) ) 
        {
            foreach( $A0c027a94ce496 as $A0c027a94ce497 ) 
            {
                if( !in_array($A0c027a94ce497, $this->sizesTmp) ) 
                {
                    unset($A0c027a94ce496[$A0c027a94ce497]);
                }

            }
        }

        return $A0c027a94ce496;
    }

    protected function prepareCropParams($O0b2301a6b, $aa5196 = ",")
    {
        if( empty($O0b2301a6b) || strpos($O0b2301a6b, $aa5196) === false || strlen($O0b2301a6b) < 10 ) 
        {
            if( $this->assignErrors ) 
            {
                $this->errors->set(sprintf("Некорректные параметры кропа [%s]", $O0b2301a6b));
            }

            return false;
        }

        list($A0c027a94ce498["x"], $A0c027a94ce498["y"], $A0c027a94ce498["x2"], $A0c027a94ce498["y2"], $A0c027a94ce498["w"], $A0c027a94ce498["h"]) = explode($aa5196, $O0b2301a6b);
        return $A0c027a94ce498;
    }

    public function setFieldImage($v2c19e29a8564)
    {
        $this->fieldImage = $v2c19e29a8564;
    }

    public function setMaxSize($J296e896e170407f8)
    {
        $this->maxSize = $J296e896e170407f8;
    }

    public function getMaxSize($f4ce1f = false, $j431655 = false)
    {
        return $f4ce1f ? tpl::filesize($this->maxSize, $j431655) : $this->maxSize;
    }

    public function doResponse($hd436f98e82e4fd02ad, $Y2624a, $P4f5898818df2)
    {
        switch( strtolower($hd436f98e82e4fd02ad) ) 
        {
            case "files":
                break;
            case "qq":
                $A0c027a94ce499 = array(  );
                if( $Y2624a !== false ) 
                {
                    $A0c027a94ce500 = $this->getURL($Y2624a["filename"], $P4f5898818df2, empty($this->recordID));
                    $A0c027a94ce499 = array_merge($A0c027a94ce499, $Y2624a, $A0c027a94ce500);
                }

                $A0c027a94ce499["success"] = $Y2624a !== false && $this->errors->no();
                $A0c027a94ce499["errors"] = $this->errors->get(true);
                $this->ajaxResponse($A0c027a94ce499, true, false, true);
                break;
            case "swf":
                break;
            case "url":
        }
    }

}


