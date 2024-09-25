<?php

namespace App\Util\Image;

use App\Util\Media\Blurhash;

/**
 * @property integer $width Image width in pixels
 * @property integer $height Image width in pixels
 * @property integer $filesize Image width in pixels
 * @property string  $blurhash Image width in pixels
 * 
 */
class ImageInfo
{
    
    private ?string $_blurhash = null;
    public function __construct(
        public string $file = '',
        public array $size = [],
    )
    {
        
    }
    public function __get($name)
    {
       if($name == 'filesize') {
            return filesize($this->file);
       }

       if($name == 'width'){
        return $this->size[0];
       }

       if($name == 'height'){
        return $this->size[1];
       }
       if($name == 'blurhash'){
        if(!$this->_blurhash){
            $this->_blurhash=Blurhash::generateV2($this->file);
        }
        return $this->_blurhash;
       }
       
    }

    
    public function toArray()
    {
       return [

       ];
    }
}