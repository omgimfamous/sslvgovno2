<?php

/**
 * Handle file uploads via XMLHttpRequest
 */
class qqUploadedFileXhr {
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path)
    {
        $input = fopen("php://input", "r");
        $target = fopen($path, "w");
        $realSize = stream_copy_to_stream($input, $target);
        fclose($target);
        fclose($input);
        if ($realSize != $this->getSize()) {
            return false;
        }
        return true;
    }
    function getName() {
        return $_GET['qqfile'];
    }
    function getSize() {
        if (isset($_SERVER["CONTENT_LENGTH"])){
            return (int)$_SERVER["CONTENT_LENGTH"];            
        } else {
            throw new \Exception('Getting content length is not supported.');
        }      
    }   
}

/**
 * Handle file uploads via regular form post (uses the $_FILES array)
 */
class qqUploadedFileForm {  
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {
        if(!move_uploaded_file($_FILES['qqfile']['tmp_name'], $path)){
            return false;
        }
        return true;
    }
    function getName() {
        return $_FILES['qqfile']['name'];
    }
    function getSize() {
        return $_FILES['qqfile']['size'];
    }
}

class qqFileUploader 
{
    private $allowedExtensions = array();
    private $sizeLimit = 10485760;
    private $assignErrors = true;
    private $file;
    private $errors;

    function __construct(array $allowedExtensions = array(), $sizeLimit = 10485760, $assignErrors = true)
    {
        $allowedExtensions = array_map("strtolower", $allowedExtensions);
            
        $this->allowedExtensions = $allowedExtensions;        
        $this->sizeLimit = $sizeLimit;
        $this->assignErrors = $assignErrors;
        $this->errors = Errors::i();
        
        //$this->checkServerSettings();

        if (isset($_GET['qqfile'])) {
            $this->file = new qqUploadedFileXhr();
        } elseif (isset($_FILES['qqfile'])) {
            $this->file = new qqUploadedFileForm();
        } else {
            $this->file = false; 
        }
    }

    public function getFilename()
    {
        return $this->file->getName();
    }

    public function getFilenameExtension()
    {
        static $ext;
        if(!isset($ext)) {
            $filename = $this->file->getName();
            $ext = bff\utils\Files::getExtension($filename);
        }
        return $ext;
    }

    public function getFilesize()
    {
        return $this->file->getSize();
    }

    private function checkServerSettings()
    {
        $postSize = $this->toBytes(ini_get('post_max_size'));
        $uploadSize = $this->toBytes(ini_get('upload_max_filesize'));        
        
        if ($postSize < $this->sizeLimit || $uploadSize < $this->sizeLimit) {
            $size = max(1, $this->sizeLimit / 1024 / 1024) . 'M';
            if ($this->assignErrors)
                $this->errors->setUploadError(Errors::FILE_DISK_QUOTA);
            return false;  
        }
    }

    private function toBytes($str)
    {
        $val = trim($str);
        $last = strtolower($str[strlen($str)-1]);
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;        
        }
        return $val;
    }
    
    /**
     * Загрузка файла
     * @param string $sFilenameDest путь к файлу (куда выполняется загрузка)
     * @return boolean
     */
    function upload($sFilenameDest = '')
    {
        if ( ! $this->file) {
            if ($this->assignErrors)
                $this->errors->setUploadError(Errors::FILE_UPLOAD_ERROR);
            return false;
        }
        
        $size = $this->file->getSize();
        
        if ($size == 0) {
            if ($this->assignErrors)
                $this->errors->setUploadError(Errors::FILE_WRONG_SIZE);
            return false;
        }
        
        if ($size > $this->sizeLimit) {
            if ($this->assignErrors)
                $this->errors->setUploadError(Errors::FILE_MAX_SIZE);
            return false;
        }
        
        $ext = $this->getFilenameExtension();

        if ($this->allowedExtensions && ! in_array($ext, $this->allowedExtensions)) {
            if ($this->assignErrors)
                $this->errors->setUploadError(Errors::FILE_WRONG_TYPE);
            return false;
        }

        if ($this->file->save($sFilenameDest)) {
            return true;
        } else {
            if ($this->assignErrors)
                $this->errors->setUploadError(Errors::FILE_UPLOAD_ERROR);
            return false;
        }
        
    }
}