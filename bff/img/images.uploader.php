<?php 
use bff\img\Thumbnail;

class CImagesUploader extends Component
{
    protected $recordID = 0;
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

    public function __construct($d9c774 = 0)
    {
        $this->init();
        $this->setRecordID($d9c774);
    }

    public function setRecordID($jf62b8625f)
    {
        $this->recordID = $jf62b8625f;
    }

    public function uploadFILES($P25a02fafd9fcd18)
    {
        $A0c027a94ce501 = false;
        $A0c027a94ce502 = false;
        while( empty($_FILES[$P25a02fafd9fcd18]) || $_FILES[$P25a02fafd9fcd18]["error"] == UPLOAD_ERR_NO_FILE ) 
        {
            break;
        }
        $A0c027a94ce503 = $_FILES[$P25a02fafd9fcd18];
        if( !$this->checkSize($A0c027a94ce503["size"]) ) 
        {
            break;
        }

        if( preg_match("/[\\/:;*?\"<>|]/i", $A0c027a94ce503["name"]) ) 
        {
            if( $this->assignErrors ) 
            {
                $this->errors->setUploadError(Errors::FILE_WRONG_NAME);
            }

            break;
        }

        $A0c027a94ce504 = bff\utils\Files::getExtension($A0c027a94ce503["name"]);
        if( !empty($this->extensionsAllowed) && !in_array($A0c027a94ce504, $this->extensionsAllowed) ) 
        {
            if( $this->assignErrors ) 
            {
                $this->errors->setUploadError(Errors::FILE_WRONG_TYPE);
            }

            break;
        }

        $A0c027a94ce502 = $this->pathTmp . func::generator(10) . "." . $A0c027a94ce504;
        if( !move_uploaded_file($A0c027a94ce503["tmp_name"], $A0c027a94ce502) ) 
        {
            if( $this->assignErrors ) 
            {
                $this->errors->setUploadError(Errors::FILE_UPLOAD_ERROR);
            }

            $A0c027a94ce502 = false;
            break;
        }

        $A0c027a94ce505 = getimagesize($A0c027a94ce502);
        if( $A0c027a94ce505 === false || !in_array($A0c027a94ce505[2], array( IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG )) ) 
        {
            if( $this->assignErrors ) 
            {
                $this->errors->setUploadError(Errors::FILE_WRONG_TYPE);
            }

            break;
        }

        $A0c027a94ce501 = $this->save(array( "ext" => $A0c027a94ce504, "tmpfile" => $A0c027a94ce502, "width" => $A0c027a94ce505[0], "height" => $A0c027a94ce505[1] ));
        if( !false ) 
        {
            $this->deleteTmpUploadedFile($A0c027a94ce502);
            return $A0c027a94ce501;
        }

    }

    public function uploadFromFile($a2682a1f06, $c92c0babdc764d8 = true)
    {
        $A0c027a94ce506 = false;
        while( empty($a2682a1f06) || !file_exists($a2682a1f06) ) 
        {
            break;
        }
        if( !$this->checkSize(filesize($a2682a1f06)) ) 
        {
            break;
        }

        $A0c027a94ce507 = pathinfo($a2682a1f06, PATHINFO_BASENAME);
        if( preg_match("/[\\/:;*?\"<>|]/i", $A0c027a94ce507) ) 
        {
            if( $this->assignErrors ) 
            {
                $this->errors->setUploadError(Errors::FILE_WRONG_NAME);
            }

            break;
        }

        $A0c027a94ce508 = bff\utils\Files::getExtension($A0c027a94ce507);
        if( !empty($this->extensionsAllowed) && !in_array($A0c027a94ce508, $this->extensionsAllowed) ) 
        {
            if( $this->assignErrors ) 
            {
                $this->errors->setUploadError(Errors::FILE_WRONG_TYPE);
            }

            break;
        }

        $A0c027a94ce509 = getimagesize($a2682a1f06);
        if( $A0c027a94ce509 === false || !in_array($A0c027a94ce509[2], array( IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG )) ) 
        {
            if( $this->assignErrors ) 
            {
                $this->errors->setUploadError(Errors::FILE_WRONG_TYPE);
            }

            break;
        }

        $A0c027a94ce506 = $this->save(array( "ext" => $A0c027a94ce508, "tmpfile" => $a2682a1f06, "width" => $A0c027a94ce509[0], "height" => $A0c027a94ce509[1] ), $c92c0babdc764d8);
        if( !false ) 
        {
            return $A0c027a94ce506;
        }

    }

    public function uploadQQ()
    {
        require_once(PATH_CORE . "external" . DS . "qquploader.php");
        $A0c027a94ce510 = new qqFileUploader($this->extensionsAllowed, $this->maxSize);
        $A0c027a94ce511 = $A0c027a94ce510->getFilenameExtension();
        $A0c027a94ce512 = false;
        $A0c027a94ce513 = $this->buildTmpUploadFilename($A0c027a94ce511);
        while( $A0c027a94ce510->upload($A0c027a94ce513) !== true ) 
        {
            break;
        }
        $A0c027a94ce514 = getimagesize($A0c027a94ce513);
        $A0c027a94ce512 = $this->save(array( "ext" => $A0c027a94ce511, "tmpfile" => $A0c027a94ce513, "width" => $A0c027a94ce514[0], "height" => $A0c027a94ce514[1] ));
        if( !false ) 
        {
            $this->deleteTmpUploadedFile($A0c027a94ce513);
            return $A0c027a94ce512;
        }

    }

    public function uploadSWF()
    {
        $A0c027a94ce515 = false;
        $A0c027a94ce516 = CUploader::swfuploadStart(true, $this->extensionsAllowed);
        if( is_string($A0c027a94ce516) ) 
        {
            if( $this->assignErrors ) 
            {
                $this->errors->set($A0c027a94ce516);
            }

            break;
        }

        $A0c027a94ce515 = $this->save(array( "ext" => $A0c027a94ce516["ext"], "tmpfile" => $A0c027a94ce516["tmp_name"], "width" => $A0c027a94ce516["width"], "height" => $A0c027a94ce516["height"] ));
        $this->deleteTmpUploadedFile($A0c027a94ce516["tmp_name"]);
        if( !false ) 
        {
            return $A0c027a94ce515;
        }

    }

    public function uploadURL($n4bc0fac6b5ab56ebcd)
    {
        $A0c027a94ce517 = false;
        $A0c027a94ce518 = false;
        while( empty($n4bc0fac6b5ab56ebcd) ) 
        {
            break;
        }
        $A0c027a94ce519 = function_exists("getimagesizefromstring");
        if( !$A0c027a94ce519 ) 
        {
            $A0c027a94ce520 = getimagesize($n4bc0fac6b5ab56ebcd);
            if( empty($A0c027a94ce520) || empty($A0c027a94ce520[0]) && empty($A0c027a94ce520[1]) || empty($A0c027a94ce520[2]) ) 
            {
                if( $this->assignErrors ) 
                {
                    $this->errors->setUploadError(Errors::FILE_UPLOAD_ERROR);
                }

                break;
            }

            $A0c027a94ce521 = image_type_to_extension($A0c027a94ce520[2], false);
            if( !in_array($A0c027a94ce521, $this->extensionsAllowed) ) 
            {
                if( $this->assignErrors ) 
                {
                    $this->errors->setUploadError(Errors::FILE_WRONG_TYPE);
                }

                break;
            }

            if( !$this->checkDimensions($A0c027a94ce520[0], $A0c027a94ce520[1]) ) 
            {
                break;
            }

        }

        $A0c027a94ce518 = $this->buildTmpUploadFilename("tmp");
        $A0c027a94ce522 = file_get_contents($n4bc0fac6b5ab56ebcd);
        if( empty($A0c027a94ce522) ) 
        {
            if( $this->assignErrors ) 
            {
                $this->errors->setUploadError(Errors::FILE_UPLOAD_ERROR);
            }

            break;
        }

        if( $A0c027a94ce519 ) 
        {
            $A0c027a94ce520 = getimagesizefromstring($A0c027a94ce522);
            if( empty($A0c027a94ce520) || empty($A0c027a94ce520[0]) && empty($A0c027a94ce520[1]) || empty($A0c027a94ce520[2]) ) 
            {
                if( $this->assignErrors ) 
                {
                    $this->errors->setUploadError(Errors::FILE_UPLOAD_ERROR);
                }

                break;
            }

            $A0c027a94ce521 = image_type_to_extension($A0c027a94ce520[2], false);
            if( !in_array($A0c027a94ce521, $this->extensionsAllowed) ) 
            {
                if( $this->assignErrors ) 
                {
                    $this->errors->setUploadError(Errors::FILE_WRONG_TYPE);
                }

                break;
            }

            if( !$this->checkDimensions($A0c027a94ce520[0], $A0c027a94ce520[1]) ) 
            {
                break;
            }

        }

        $A0c027a94ce523 = file_put_contents($A0c027a94ce518, $A0c027a94ce522, LOCK_EX);
        if( $A0c027a94ce523 === false ) 
        {
            if( $this->assignErrors ) 
            {
                $this->errors->set(_t("uploader", "Неудалось сохранить файл во временную папку"), true);
                $this->errors->setUploadError(Errors::FILE_UPLOAD_ERROR);
            }

            break;
        }

        $A0c027a94ce524 = filesize($A0c027a94ce518);
        if( !$this->checkSize($A0c027a94ce524) ) 
        {
            break;
        }

        $A0c027a94ce517 = $this->save(array( "ext" => $A0c027a94ce521, "tmpfile" => $A0c027a94ce518, "width" => $A0c027a94ce520[0], "height" => $A0c027a94ce520[1] ));
        if( !false ) 
        {
            $this->deleteTmpUploadedFile($A0c027a94ce518);
            return $A0c027a94ce517;
        }

    }

    protected function save($g58d155f6746e, $a84c3f024 = false)
    {
        if( empty($g58d155f6746e) || empty($g58d155f6746e["tmpfile"]) ) 
        {
            return false;
        }

        if( !$this->checkDimensions($g58d155f6746e["width"], $g58d155f6746e["height"]) ) 
        {
            return false;
        }

        $A0c027a94ce525 = empty($this->recordID);
        $A0c027a94ce526 = $this->getSizes(false, $A0c027a94ce525);
        $A0c027a94ce527 = func::generator($this->filenameLetters) . "." . $g58d155f6746e["ext"];
        $A0c027a94ce528 = new Thumbnail($g58d155f6746e["tmpfile"], $a84c3f024);
        $this->checkFolderByID();
        $A0c027a94ce529 = array(  );
        $A0c027a94ce530 = $this->getRandServer();
        $A0c027a94ce531 = $this->getDir();
        $A0c027a94ce532 = array( "filename" => $A0c027a94ce527, "dir" => $A0c027a94ce531, "srv" => $A0c027a94ce530 );
        foreach( $A0c027a94ce526 as $A0c027a94ce533 => $A0c027a94ce534 ) 
        {
            $A0c027a94ce534["filename"] = $this->getPath($A0c027a94ce532, $A0c027a94ce533, $A0c027a94ce525);
            if( !empty($A0c027a94ce534["o"]) ) 
            {
                $A0c027a94ce535 = $A0c027a94ce534["filename"];
                if( (!empty($A0c027a94ce534["width"]) || !empty($A0c027a94ce534["height"])) && $A0c027a94ce534["width"] < $A0c027a94ce528->getOriginalWidth() ) 
                {
                    $A0c027a94ce529[] = $A0c027a94ce534;
                }
                else
                {
                    $A0c027a94ce536 = copy($g58d155f6746e["tmpfile"], $A0c027a94ce535);
                    if( !$A0c027a94ce536 ) 
                    {
                        if( $this->assignErrors ) 
                        {
                            $this->errors->set(_t("uploader", "Неудалось сохранить оригинал изображения \"[path]\"", array( "path" => $A0c027a94ce535 )), true);
                        }

                        return false;
                    }

                }

            }
            else
            {
                if( !empty($A0c027a94ce534["vertical"]) && $A0c027a94ce528->isVertical() ) 
                {
                    $A0c027a94ce529[] = array_merge($A0c027a94ce534, $A0c027a94ce534["vertical"]);
                }
                else
                {
                    $A0c027a94ce529[] = $A0c027a94ce534;
                }

            }

        }
        if( !empty($A0c027a94ce529) && !$A0c027a94ce528->save($A0c027a94ce529) ) 
        {
            $A0c027a94ce537 = current($A0c027a94ce529);
            if( $this->assignErrors ) 
            {
                $this->errors->set(_t("uploader", "Неудалось сохранить изображение \"[filename]\"", array( "filename" => $A0c027a94ce537["filename"] )), true);
            }

            return false;
        }

        return array( "filename" => $A0c027a94ce527, "width" => $g58d155f6746e["width"], "height" => $g58d155f6746e["height"], "extension" => $g58d155f6746e["ext"], "dir" => $A0c027a94ce531, "srv" => $A0c027a94ce530 );
    }

    public function saveTmp($c0b958bde072 = "img", $E79f8ec909fe2e84 = false, $dce31ce = array(  ))
    {
        $this->checkFolderByID();
        $dce31ce = array_merge(array( "deleteSizes" => array(  ) ), $dce31ce);
        if( is_array($c0b958bde072) ) 
        {
            $A0c027a94ce538 = $c0b958bde072;
        }
        else
        {
            $A0c027a94ce538 = $this->input->post($c0b958bde072, TYPE_ARRAY_STR);
        }

        $A0c027a94ce539 = $this->getRandServer();
        $A0c027a94ce540 = $this->getDir();
        $A0c027a94ce541 = array( "filename" => "", "dir" => $A0c027a94ce540, "srv" => $A0c027a94ce539, "w" => 0, "h" => 0 );
        $A0c027a94ce542 = array(  );
        $A0c027a94ce543 = !empty($this->sizesTmp) && sizeof($this->sizesTmp) < sizeof($this->sizes);
        if( $A0c027a94ce543 ) 
        {
            $A0c027a94ce544 = $this->getSizes(false, true);
            $A0c027a94ce545 = $this->getSizes($this->sizesTmp, false);
            $A0c027a94ce546 = end($this->sizesTmp);
            reset($this->sizesTmp);
        }

        $A0c027a94ce547 = 0;
        foreach( $A0c027a94ce538 as $A0c027a94ce548 ) 
        {
            $A0c027a94ce541["filename"] = $A0c027a94ce548;
            if( $A0c027a94ce543 ) 
            {
                $A0c027a94ce549 = new Thumbnail($this->getPath($A0c027a94ce541, $A0c027a94ce546, true), true);
                $A0c027a94ce541["w"] = $A0c027a94ce549->getOriginalWidth();
                $A0c027a94ce541["h"] = $A0c027a94ce549->getOriginalHeight();
                $A0c027a94ce550 = array(  );
                foreach( $A0c027a94ce545 as $A0c027a94ce551 => $A0c027a94ce552 ) 
                {
                    $A0c027a94ce552["filename"] = $this->getPath($A0c027a94ce541, $A0c027a94ce551, false);
                    $A0c027a94ce550[] = $A0c027a94ce552;
                }
                if( !$A0c027a94ce549->save($A0c027a94ce550) ) 
                {
                    $A0c027a94ce553 = current($A0c027a94ce550);
                    if( $this->assignErrors ) 
                    {
                        $this->errors->set(_t("uploader", "Неудалось сохранить изображение \"[filename]\"", array( "filename" => $A0c027a94ce553["filename"] )), true);
                    }

                    continue;
                }

                foreach( $A0c027a94ce544 as $A0c027a94ce551 => $A0c027a94ce552 ) 
                {
                    $pathTmp = $this->getPath($A0c027a94ce541, $A0c027a94ce551, true);
                    $A0c027a94ce554 = $this->getPath($A0c027a94ce541, $A0c027a94ce551, false);
                    @rename($pathTmp, $A0c027a94ce554);
                }
            }
            else
            {
                foreach( $this->sizes as $A0c027a94ce551 => $A0c027a94ce552 ) 
                {
                    $pathTmp = $this->getPath($A0c027a94ce541, $A0c027a94ce551, true);
                    $A0c027a94ce554 = $this->getPath($A0c027a94ce541, $A0c027a94ce551, false);
                    $A0c027a94ce555 = @rename($pathTmp, $A0c027a94ce554);
                    if( !$A0c027a94ce555 ) 
                    {
                    }

                }
                if( !empty($A0c027a94ce554) && file_exists($A0c027a94ce554) ) 
                {
                    $A0c027a94ce556 = getimagesize($A0c027a94ce554);
                    if( !empty($A0c027a94ce556) ) 
                    {
                        list($A0c027a94ce541["w"], $A0c027a94ce541["h"]) = $A0c027a94ce556;
                    }

                }

            }

            $A0c027a94ce542[] = $A0c027a94ce541;
            if( !empty($dce31ce["deleteSizes"]) ) 
            {
                if( is_string($dce31ce["deleteSizes"]) ) 
                {
                    $dce31ce["deleteSizes"] = array( $dce31ce["deleteSizes"] );
                }

                foreach( $dce31ce["deleteSizes"] as $A0c027a94ce557 ) 
                {
                    $path = $this->getPath($A0c027a94ce541, $A0c027a94ce557, false);
                    if( file_exists($path) ) 
                    {
                        @unlink($path);
                    }

                }
            }

            $A0c027a94ce547++;
        }
        return $A0c027a94ce542;
    }

    public function deleteTmpFile($E11206806)
    {
        if( is_array($E11206806) ) 
        {
            $A0c027a94ce558 = 0;
            foreach( $E11206806 as $A0c027a94ce559 ) 
            {
                $A0c027a94ce560 = $this->deleteFile(array( "filename" => $A0c027a94ce559 ), true);
                if( $A0c027a94ce560 ) 
                {
                    $A0c027a94ce558++;
                }

            }
            return sizeof($E11206806) === $A0c027a94ce558;
        }

        return $this->deleteFile(array( "filename" => $E11206806 ), true);
    }

    public function deleteFile($Y18ade44, $H2741c = false)
    {
        if( empty($Y18ade44) ) 
        {
            return false;
        }

        $Y18ade44["filename"] = bff\utils\Files::cleanFilename($Y18ade44["filename"]);
        foreach( $this->getSizes(false, $H2741c) as $A0c027a94ce561 => $A0c027a94ce562 ) 
        {
            $path = $this->getPath($Y18ade44, $A0c027a94ce561, $H2741c);
            if( file_exists($path) ) 
            {
                @unlink($path);
            }

        }
        return true;
    }

    protected function buildTmpUploadFilename($g3671077b66)
    {
        return $this->pathTmp . $this->recordID . "tmp" . func::generator($this->filenameLetters) . "." . $g3671077b66;
    }

    protected function deleteTmpUploadedFile($D3682a)
    {
        if( !empty($D3682a) && file_exists($D3682a) && is_writable(pathinfo($D3682a, PATHINFO_DIRNAME)) ) 
        {
            return @unlink($D3682a);
        }

        return false;
    }

    protected function checkDimensions($C2f3b5f70d1547e76, $y254e73826)
    {
        if( 0 < $this->minWidth && $C2f3b5f70d1547e76 < $this->minWidth ) 
        {
            if( $this->assignErrors ) 
            {
                $this->errors->set(_t("uploader", "Изображение меньше допустимой ширины [width]px", array( "width" => $this->minWidth )));
            }

            return false;
        }

        if( 0 < $this->maxWidth && $this->maxWidth < $C2f3b5f70d1547e76 ) 
        {
            if( $this->assignErrors ) 
            {
                $this->errors->set(_t("uploader", "Изображение больше допустимой ширины [width]px", array( "width" => $this->maxWidth )));
            }

            return false;
        }

        if( 0 < $this->minHeight && $y254e73826 < $this->minHeight ) 
        {
            if( $this->assignErrors ) 
            {
                $this->errors->set(_t("uploader", "Изображение меньше допустимой высоты [height]px", array( "height" => $this->minHeight )));
            }

            return false;
        }

        if( 0 < $this->maxHeight && $this->maxHeight < $y254e73826 ) 
        {
            if( $this->assignErrors ) 
            {
                $this->errors->set(_t("uploader", "Изображение больше допустимой высоты [height]px", array( "height" => $this->maxHeight )));
            }

            return false;
        }

        return true;
    }

    public function setDimensions($B50b23508b = 0, $bfda7b8cb4ea99 = 0, $uf6cbebfb6e2b5c4ad = 0, $l09bcb367d6 = 0)
    {
        $this->minWidth = $B50b23508b;
        $this->minHeight = $bfda7b8cb4ea99;
        $this->maxWidth = $uf6cbebfb6e2b5c4ad;
        $this->maxHeight = $l09bcb367d6;
    }

    public function getImageParams($c392f918883841e3bf, $f31aa2cc, $c48a5e1d = false)
    {
        $A0c027a94ce563 = array(  );
        if( empty($c392f918883841e3bf) || empty($f31aa2cc) ) 
        {
            return $A0c027a94ce563;
        }

        foreach( $f31aa2cc as $A0c027a94ce564 ) 
        {
            $path = $this->getPath($c392f918883841e3bf, $A0c027a94ce564, $c48a5e1d);
            list($A0c027a94ce565, $A0c027a94ce566) = getimagesize($path);
            $A0c027a94ce563[$A0c027a94ce564] = array( "w" => $A0c027a94ce565, "h" => $A0c027a94ce566 );
            if( sizeof($f31aa2cc) == 1 ) 
            {
                return $A0c027a94ce563[$A0c027a94ce564];
            }

        }
        return $A0c027a94ce563;
    }

    public function setAssignErrors($ea05589f6e1)
    {
        $this->assignErrors = $ea05589f6e1;
    }

    protected function checkSize($V89096d2e0151550)
    {
        if( !$V89096d2e0151550 ) 
        {
            if( $this->assignErrors ) 
            {
                $this->errors->setUploadError(Errors::FILE_WRONG_SIZE);
            }

            return false;
        }

        if( 0 < $this->maxSize && $this->maxSize < $V89096d2e0151550 ) 
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
            $A0c027a94ce567 = $this->path . intval(($this->recordID + 100) / 1000);
            if( !file_exists($A0c027a94ce567) ) 
            {
                bff\utils\Files::makeDir($A0c027a94ce567);
            }

        }

    }

    public function setFolderByID($Bc7b3e)
    {
        $this->folderByID = $Bc7b3e;
    }

    protected function getPath($aa908f70afe, $f5071852, $z0ea6629919566d = false)
    {
        return ($z0ea6629919566d ? $this->pathTmp . "0" : $this->path . ($this->folderByID ? (isset($aa908f70afe["dir"]) ? $aa908f70afe["dir"] : $this->getDir()) . DS : "") . $this->recordID) . $f5071852 . $aa908f70afe["filename"];
    }

    public function setPath($c99dfe59, $a8b9f45131753efd17)
    {
        $this->path = $c99dfe59;
        $this->pathTmp = $a8b9f45131753efd17;
    }

    public function getURL($Pd8ed0c, $g6647365, $e47a1c76324af = false)
    {
        if( is_array($g6647365) ) 
        {
            $A0c027a94ce568 = array(  );
            foreach( $g6647365 as $A0c027a94ce569 ) 
            {
                $A0c027a94ce568[$A0c027a94ce569] = $this->getURL($Pd8ed0c, $A0c027a94ce569, $e47a1c76324af);
            }
            return $A0c027a94ce568;
        }

        return ($e47a1c76324af ? $this->urlTmp . "0" : $this->url . ($this->folderByID ? (isset($Pd8ed0c["dir"]) ? $Pd8ed0c["dir"] : $this->getDir()) . "/" : "") . $this->recordID) . $g6647365 . $Pd8ed0c["filename"];
    }

    public function setURL($x62d364462c790, $af7a32555eaca28c406)
    {
        $this->url = $x62d364462c790;
        $this->urlTmp = $af7a32555eaca28c406;
    }

    public static function url($C29033434babc227c, $D565801e1ec38, $f9534f952526e2ea, $z7724dfa800a05c8 = false)
    {
        static $b56a57731a;
        if( !isset($b56a57731a) ) 
        {
            $A0c027a94ce570 = get_called_class();
            $b56a57731a = new $A0c027a94ce570();
        }

        $b56a57731a->setRecordID($C29033434babc227c);
        return $b56a57731a->getURL($D565801e1ec38, $f9534f952526e2ea, $z7724dfa800a05c8);
    }

    protected function getDir()
    {
        return (string) intval($this->recordID / 1000);
    }

    protected function getRandServer()
    {
        return 1;
    }

    protected function getSizes($Scf06fca7a54a41ed = array(  ), $z14d0c67e71c1c272 = false)
    {
        $A0c027a94ce571 = $this->sizes;
        if( !empty($Scf06fca7a54a41ed) ) 
        {
            if( !is_array($Scf06fca7a54a41ed) ) 
            {
                $Scf06fca7a54a41ed = array( $Scf06fca7a54a41ed );
            }

            foreach( $Scf06fca7a54a41ed as $A0c027a94ce572 ) 
            {
                if( isset($A0c027a94ce571[$A0c027a94ce572]) ) 
                {
                    unset($A0c027a94ce571[$A0c027a94ce572]);
                }

            }
        }

        if( $z14d0c67e71c1c272 === true && !empty($this->sizesTmp) ) 
        {
            foreach( $A0c027a94ce571 as $A0c027a94ce572 => $A0c027a94ce573 ) 
            {
                if( !in_array($A0c027a94ce572, $this->sizesTmp) ) 
                {
                    unset($A0c027a94ce571[$A0c027a94ce572]);
                }

            }
        }

        return $A0c027a94ce571;
    }

    public function setSizes($fd56eac = array(  ), $Wb2621b3dc97e6 = array(  ))
    {
        if( empty($fd56eac) ) 
        {
            if( $this->assignErrors ) 
            {
                $this->errors->set(_t("uploader", "Неуказаны требуемые размеры изображения"), true);
            }

        }
        else
        {
            $this->sizes = $fd56eac;
            foreach( $this->sizes as $A0c027a94ce574 => $A0c027a94ce575 ) 
            {
                $this->sizes[$A0c027a94ce574]["quality"] = $this->quality;
            }
        }

        $this->sizesTmp = $Wb2621b3dc97e6;
    }

    public function getSizeWidth($Y797ed)
    {
        return !empty($this->sizes[$Y797ed]["width"]) ? $this->sizes[$Y797ed]["width"] : 0;
    }

    public function getSizeHeight($e9ffcb)
    {
        return !empty($this->sizes[$e9ffcb]["height"]) ? $this->sizes[$e9ffcb]["height"] : 0;
    }

    public function getMaxSize($e3ce24f3ad7b = false, $C7cc8ad03 = false)
    {
        return $e3ce24f3ad7b ? tpl::filesize($this->maxSize, $C7cc8ad03) : $this->maxSize;
    }

    public function setMaxSize($Qd88bd01c4)
    {
        $this->maxSize = $Qd88bd01c4;
    }

}


