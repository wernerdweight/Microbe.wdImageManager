<?php

namespace WernerDweight\MicrobeImageManager\Utils;

use WernerDweight\MicrobeImageManager\File\UploadedFile;
use WernerDweight\ImageManager\Manager\ImageManager;
use WernerDweight\ImageManager\Image\ProcessedImageBag;

class ImageManagerUtility
{
    protected $uploadRoot;
    protected $uploadPath;
    protected $assetPath;
    protected $customPath;
    protected $secret;
    protected $versions;
    protected $destinationFilename;
    protected $originalExtension;
    protected $processedImageBag;
    protected $im;

    public function __construct($configuration){
        $this->versions = $configuration['versions'];
        $this->uploadRoot = $configuration['upload_root'];
        $this->uploadPath = $configuration['upload_path'];
        $this->secret = $configuration['secret'];
    }

    protected function createVersions(){
        $this->im = new ImageManager($this->secret);
        try {
            foreach ($this->versions as $versionName => $version) {
                /// load image data from file as resource data had changed
                $this->im->loadImage($this->assetPath);
                /// if resize dimensions are specified resize image
                if(isset($version['width']) && isset($version['height']) && intval($version['width']) > 0 && intval($version['height']) > 0){
                    /// if resize dimensions are smaller (or equal) than original dimensions use resize dimensions
                    if(intval($version['width']) <= $this->processedImageBag->getOriginalWidth() || intval($version['height']) <= $this->processedImageBag->getOriginalHeight()){
                        $this->im->resize($version['width'],$version['height'],boolval($version['crop']));
                    }
                    /// if resize dimensions are larger than original dimensions and crop is set use original dimensions and adjust their ratio to fit the resize dimensions ratio
                    else if(isset($version['crop']) && $version['crop'] === true){
                        $resizeRatio = intval($version['width'])/intval($version['height']);
                        $originalRatio = $this->processedImageBag->getOriginalWidth()/$this->processedImageBag->getOriginalHeight();
                        /// if resize dimensions are wider crop original height
                        if($resizeRatio > $originalRatio){
                            $newWidth = $this->processedImageBag->getOriginalWidth();
                            $newHeight = $this->processedImageBag->getOriginalHeight() * ($originalRatio / $resizeRatio);
                        }
                        /// if resize dimensions are taller crop original width
                        else{
                            $newWidth = $this->processedImageBag->getOriginalWidth() * ($resizeRatio / $originalRatio);
                            $newHeight = $this->processedImageBag->getOriginalHeight();
                        }
                        $this->im->crop($newWidth,$newHeight);
                    }
                    /// if resize dimensions are larger and crop is not set take no action just save the image as is (in order to prevent upscaling)
                }
                /// if version is set to be encrypted encrypt it
                if(isset($version['encrypted']) && $version['encrypted'] === true){
                    $this->im->encrypt();
                }
                /// save the newly created image version to its destination
                $this->im->saveImage($this->uploadRoot.DIRECTORY_SEPARATOR.$this->uploadPath.$this->customPath.'/'.$versionName.'/',$this->destinationFilename,(isset($version['type']) ? $version['type'] : null),(isset($version['quality']) ? $version['quality'] : 75));
            }
            /// delete original file as we won't need it anymore
            $this->unlinkOriginalFile();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected function unlinkOriginalFile(){
        try {
            unlink($this->assetPath);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected function createUniqueFilename($filename,$extension){
        /// chceck that this file does not yet exist
        $uniquePart = '';       /// string to be appended if filename not unique
        $i = 0;                 /// unique title iterator
        /// check for each image version
        foreach ($this->versions as $versionName => $version) {
            /// while file exists iterate counter to be appended to filename (filename -> filename-1 -> filename-2 -> ...)
            while(file_exists($this->uploadRoot.'/'.$this->uploadPath.$this->customPath.'/'.$versionName.'/'.$filename.$uniquePart.'.'.(isset($version['type']) ? $version['type'] : $extension))){
                $i++;
                $uniquePart = '-'.$i;
            }
        }
        /// append unique string (empty if no conflict)
        return $filename.$uniquePart;
    }

    protected function preparePaths($destinationFilename, $photoFile, $customPath = null, $extension = null){
        $this->customPath = $customPath;
        $this->destinationFilename = $this->createUniqueFilename($destinationFilename,(null !== $photoFile ? $photoFile->getExtension() : $extension));
        $this->assetPath = $this->uploadRoot.DIRECTORY_SEPARATOR.$this->uploadPath.$this->customPath.'/'.$this->destinationFilename.'.'.(null !== $photoFile ? $photoFile->getExtension() : $extension);
    }

    public function processImage(UploadedFile $photoFile, $destinationFilename, $customPath = null)
    {
        $this->preparePaths($destinationFilename,$photoFile,$customPath);

        try {
            /// move file to temporary destination
            $photoFile->move($this->uploadRoot.DIRECTORY_SEPARATOR.$this->uploadPath.$this->customPath,$this->destinationFilename.'.'.$photoFile->getExtension());
            $this->processedImageBag = new ProcessedImageBag($this->assetPath,$photoFile->getClientOriginalName());
            /// create versions according to the configuration
            $this->createVersions();
        } catch (\Exception $e) {
            throw $e;
        }
        /// return bag of data helpful for persisting image info
        return $this->processedImageBag;
    }

    public function processExistingImage($destinationFilename, $extension, $name, $customPath = null)
    {

        $this->preparePaths($destinationFilename,null,$customPath,$extension);

        try {
            $this->processedImageBag = new ProcessedImageBag($this->assetPath,$name);
            /// create versions according to the configuration
            $this->createVersions();
        } catch (\Exception $e) {
            throw $e;
        }
        /// return bag of data helpful for persisting image info
        return $this->processedImageBag;
    }
}
