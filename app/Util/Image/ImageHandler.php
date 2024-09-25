<?php

namespace App\Util\Image;

use App\Aspect\Annotation\ExecTimeLogger;
use App\Service\AttachmentServiceV2;
use App\Util\Media\Blurhash;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use Jcupitt\Vips\Image;

use function Hyperf\Coroutine\defer;

class ImageHandler
{


    public function __construct(public string $filepath,public string $ext)
    {
        
    }

    public function getInfo()
    {
       $image = Image::newFromFile($this->filepath);
       return new ImageInfo(
            $this->filepath,
            [$image->width,$image->height]
        );
    }
    

    #[ExecTimeLogger('attachment_download')]
    public function resizeThumb($width = 500,$options = ['size' => 'down'])
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'thumbnail'). ".$this->ext";
        $image = Image::newFromFile($this->filepath); 
        $image = $image->thumbnail_image($width,$options);
        $image->writeToFile($tmpFile);


        defer(fn() => unlink($tmpFile));
        return new ImageInfo(
            $tmpFile,
            [$image->width,$image->height]
        );
    }
    
    #[ExecTimeLogger('attachment_download')]
    public function generateVideoPoster()
    {
        $videoPoster = tempnam(sys_get_temp_dir(), 'video-poster');
        
        $ffmpeg = FFMpeg::create();
        $video = $ffmpeg->open($this->filepath);
        $video->frame(TimeCode::fromSeconds(1))->save($videoPoster);

        $image = Image::newFromFile($videoPoster); 
        defer(fn() => unlink($videoPoster)); 

        return new ImageInfo(
            $videoPoster,
            [$image->width,$image->height]
        ); 
    }

}