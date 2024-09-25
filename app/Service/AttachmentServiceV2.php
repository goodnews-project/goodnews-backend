<?php

namespace App\Service;

use App\Exception\AttachmentDownloadException;
use App\Model\Attachment;
use App\Nsq\NsqQueueMessage;
use App\Service\Activitypub\ActivitypubService;
use App\Util\Log;
use App\Util\Media\Blurhash;
use GuzzleHttp\RequestOptions;
use Hyperf\Contract\ContainerInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Logger\Logger;
use Hyperf\Logger\LoggerFactory;
use League\Flysystem\Filesystem;
use Hyperf\Stringable\Str;
use Jcupitt\Vips\Image;

use function Hyperf\Support\env;

class AttachmentServiceV2
{
    #[Inject]
    protected Filesystem $filesystem;

    #[Inject]
    private ClientFactory $clientFactory;

    private Logger $logger;
    static $MIMES = [
        "image/jpeg"      => 'jpeg', 
        "image/png"       => 'png', 
        "image/gif"       => 'gif', 
        "image/heic"      => 'heic', 
        "image/heif"      => 'heic', 
        "image/webp"      => 'webp', 
        "video/webm"      => 'webm', 
        "video/mp4"       => 'mp4', 
        "video/quicktime" => 'mov', 
        "video/ogg"       => 'ogg', 
    ];

    public function __construct(ContainerInterface $container)
    {
        $this->logger = $container->get(LoggerFactory::class)->get('log','attachment_download'); 
    }
   
    public function image($url)
    {
      
    }


    public static function getUa($url)
    {
        $url = parse_url($url);
        if($url['host'] === 'pbs.twimg.com' || $url['host'] === 't.co'){
            return 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.104 Safari/537.36';
        }
        return ActivitypubService::getUa();
    }
    public function download($url)
    {
        $this->logger->info("start simple download: {$url}");
        $client = $this->clientFactory->create([
            'headers' => [
                'User-Agent'   => self::getUa($url),
            ],
        ]);
        $tmpFile = tempnam(sys_get_temp_dir(), 'remote-download');
        $response = $client->get($url,[
            RequestOptions::SINK => $tmpFile
        ]);  

        $contentType = $response->getHeaderLine('content-type');
        if(!isset(self::$MIMES[$contentType])){
            throw new AttachmentDownloadException("ContentType {$contentType} not supported");
        }

        $ext = self::$MIMES[$contentType];
        $filename = $this->generateFilename($ext);
        $stream = fopen($tmpFile, 'r+');
        $this->filesystem->writeStream(
            $originLocation = '/remote/origin/'.date('Y-m-d') . "/$filename",
            $stream
        );
        fclose($stream); 
        unlink($tmpFile);
        return env('ATTACHMENT_PREFIX') . $originLocation;
    }
    public function downloadVideo($url)
    {
        // TODO 日志记录改成 AOP
        $timeStart = microtime(true);
        $this->logger->info("start download video: {$url}");

    
        $client = $this->clientFactory->create([
            'headers' => [
                'User-Agent'   => self::getUa($url),
            ],
        ]);
        $tmpFile = tempnam(sys_get_temp_dir(), 'remote-download');
        $response = $client->get($url,[
            RequestOptions::SINK => $tmpFile
        ]); 

        $timeEnd = microtime(true);
        $executioTime = ($timeEnd - $timeStart)/60;
        $this->logger->info("end download video: {$url} cost: {$executioTime}s ");

        $contentType = $response->getHeaderLine('content-type');
        if(!isset(self::$MIMES[$contentType])){
            throw new AttachmentDownloadException("ContentType {$contentType} not supported");
        }

        $ext = self::$MIMES[$contentType];
        $filename = $this->generateFilename($ext);

        $stream = fopen($tmpFile, 'r+');
        $this->filesystem->writeStream(
            $originLocation = '/remote/origin/'.date('Y-m-d') . "/$filename",
            $stream
        );
        fclose($stream);
        unlink($tmpFile);

        $this->logger->info("upload end :{$url}");
        return [
            'remote_url' => $url,
            'url'        => env('ATTACHMENT_PREFIX'). $originLocation,
            'file_type'  => Attachment::FILE_TYPE_VIDEO,
            'media_type' => $contentType,
        ]; 
    }

    public function downloadImage($url,$generateBlurhash = true)
    {
        $timeStart = microtime(true);
        $this->logger->info("start download image: {$url}");


        $client = $this->clientFactory->create([
            'headers' => [
                'User-Agent'   => self::getUa($url),
            ],
        ]);
        $tmpFile = tempnam(sys_get_temp_dir(), 'remote-download');
        $response = $client->get($url,[
            RequestOptions::SINK => $tmpFile
        ]);

        $timeEnd = microtime(true);
        $executioTime = ($timeEnd - $timeStart)/60;
        $this->logger->info("end download image: {$url} cost: {$executioTime}s ");

        $contentType = $response->getHeaderLine('content-type');
        if(!isset(self::$MIMES[$contentType])){
            throw new AttachmentDownloadException("ContentType {$contentType} not supported");
        }

        $ext = self::$MIMES[$contentType];
        $filename = $this->generateFilename($ext);

        // fixfile ext
        rename($tmpFile,$tmpFile. ".$ext");
        $tmpFile = $tmpFile. ".$ext";
        // origin image upload
        $originImage = Image::newFromFile($tmpFile);  
        $stream = fopen($tmpFile, 'r+');
        $this->filesystem->writeStream(
            $originLocation = '/remote/origin/'.date('Y-m-d') . "/$filename",
            $stream
        );
        fclose($stream);
        $this->logger->info("upload end :{$url}");


        // resize image
        [$resizeTmpFile,$thumbWidth,$thumbHeight] = $this->generateThumbnail($tmpFile,$ext);
        $resizeStream = fopen($resizeTmpFile, 'r+'); 
        $this->filesystem->writeStream(
            $thumbLocation = '/remote/thumb/'.date('Y-m-d') . "/$filename",
            $resizeStream
        );
        fclose($resizeStream); 
        $this->logger->info("resize end :{$url}");

        $blurHash = null;
        if ($generateBlurhash) {
            $blurHash = Blurhash::generate($resizeTmpFile,$contentType);
            $this->logger->info("blur hash end :{$url}");
        }
        unlink($tmpFile);
        unlink($resizeTmpFile);

        return [
            'remote_url'       => $url,
            'url'              => env('ATTACHMENT_PREFIX'). $originLocation,
            'thumbnail_url'    => env('ATTACHMENT_PREFIX'). $thumbLocation,
            'file_type'        => Attachment::FILE_TYPE_IMAGE,
            'media_type'       => $contentType,
            'blurhash'         => $blurHash['blurhash'],
            'width'            => $originImage->width,
            'height'           => $originImage->height,
            'thumbnail_width'  => $thumbWidth,
            'thumbnail_height' => $thumbHeight,
        ];
    }


    public function generateThumbnail($filepath,$ext)
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'thumbnail'). ".$ext";
        $image = Image::newFromFile($filepath); 
        $image = $image->thumbnail_image(550,[
            'size' => 'down',
        ]);
        $image->writeToFile($tmpFile);
        return [$tmpFile,$image->width,$image->height];
    }

    public function generateFilename($ext)
    {
        return Str::random(20) . "." . $ext;
    }

    #[NsqQueueMessage]
    public function asyncDownload($attachmentId)
    {
        Log::info(__FUNCTION__.' start:'.$attachmentId);
        $this->downloadById($attachmentId);
        Log::info(__FUNCTION__.' end:'.$attachmentId);
    }

    public function downloadById($attachmentId)
    {
        $attachment = Attachment::findOrFail($attachmentId);
        $info = $this->simpleDownload($attachment);
        $attachment->update($info);
        Log::info(__FUNCTION__.' updated['.$attachmentId.'] info:', $info);
    }

    public function batchDeleteWithCloud($attachments)
    {
        foreach ($attachments as $attachment) {
            if (isLocalAp($attachment->url)) {
                $this->filesystem->delete(str_replace(env('ATTACHMENT_PREFIX'), '', $attachment->url));
            }

            $attachment->delete();
        }
    }

    public function simpleDownload(Attachment $attachment)
    {
        $url = $attachment->remote_url;
        $mediaType = $attachment->media_type;

        $blurhash = $attachment->blurhash;
        $file_type = Attachment::FILE_TYPE_IMAGE;
        $info = [];
        if (str_contains($mediaType, 'video')) {
            $file_type = Attachment::FILE_TYPE_VIDEO;
            $info = $this->downloadVideo($url);
        } elseif (str_contains($mediaType, 'audio')) {
            $file_type = Attachment::FILE_TYPE_AUDIO;
        } else {
            $info = $this->downloadImage($url, $blurhash);
        }
        $info['file_type'] = $file_type;
        return $info;
    }
}
