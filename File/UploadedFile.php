<?php

namespace WernerDweight\MicrobeImageManager\File;

class UploadedFile extends \SplFileInfo
{
	protected $originalName;
	protected $mimeType;

	protected function getFileame($filename){
        /// remove slashes from the (temporary) filename
        $originalName = str_replace('\\','/',$filename);
        $pos = strrpos($originalName,'/');
        return (false === $pos ? $originalName : substr($originalName,$pos+1));
    }

	public function __construct($path,$originalName,$mimeType = null){
		$this->originalName = $this->getFilename($originalName);
        $this->mimeType = $mimeType ? $mimeType : 'application/octet-stream';

        parent::__construct($path);
	}

	public function getExtension(){
		return pathinfo($this->getPathname(),PATHINFO_EXTENSION);
	}

	public function getClientOriginalName(){
		return $this->originalName;
	}

	public function move($destination,$filename){
		/// check that file actually exists
		if(!is_uploaded_file($this->getPathname())){
			throw new \Exception($this->getErrorMessage());
		}

		/// chceck that destination directory exists
		if(!is_dir($destination)){
			/// try to create the directory
            if(false === @mkdir($destination,0777,true) && !is_dir($destination)){
                throw new \Exception('Can\'t create directory "'.$destination.'"');
            }
        }
        /// check access rights
        else if(!is_writable($destination)){
            throw new \Exception('Can\'t write to directory "'.$destination.'"');
        }

        /// determine target path
        $target = rtrim($destination,'/\\').DIRECTORY_SEPARATOR.$this->getFilename($filename);

		/// move the file
		if(!@move_uploaded_file($this->getPathname(), $target)){
			throw new \Exception('Can\'t move the file from "'.$this->getPathname().'" to "'.$target.'"');
		}

		/// set access rights
		@chmod($target,0666 & ~umask());

		return $target;
	}
}
