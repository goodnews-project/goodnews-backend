<?php

namespace App\Util\Image;

use Hyperf\HttpMessage\Upload\UploadedFile;
use Jcupitt\Vips\SourceCustom;
use Jcupitt\Vips\Image;
use Jcupitt\Vips\TargetCustom;

class ImageStream
{
    public $source;
    public function __construct(public &$stream,public $extension){
       
        $this->source = new SourceCustom();
        $this->source->onRead(function ($bufferLength) use(&$stream){
            return fread($stream, $bufferLength);
        });
    }

    public function createImage():Image
    {
       return  Image::newFromSource($this->source);
    }

    public function write($image)
    {
        $outStream = fopen('php://temp', 'rw');
        $target = new TargetCustom();
        $target->onWrite(function ($buffer) use(&$outStream){
            $result = fwrite($outStream, $buffer);
            if ($result === false) {
                return -1;
            } else {
                return $result;
            }
        });
        // read and seek are optional
        $target->onSeek(function ($offset, $whence) use(&$outStream){
            if (fseek($outStream, $offset, $whence)) {
                return -1;
            }
            return ftell($outStream);
        });
        $target->onRead(function ($bufferLength) use(&$outStream){
            return fread($outStream, $bufferLength);
        });
        $image->writeToTarget($target,".{$this->extension}");
        return $outStream;
    }
}
