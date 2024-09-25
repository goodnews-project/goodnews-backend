<?php

namespace App\Service;

use App\Aspect\Annotation\ExecTimeLogger;
use App\Exception\AttachmentDownloadException;
use App\Model\Attachment;
use App\Nsq\NsqQueueMessage;
use App\Service\Activitypub\ActivitypubService;
use App\Util\Image\ImageHandler;
use GuzzleHttp\RequestOptions;
use Hyperf\Contract\ContainerInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Logger\Logger;
use Hyperf\Logger\LoggerFactory;
use League\Flysystem\Filesystem;
use Hyperf\Stringable\Str;
use function Hyperf\Coroutine\defer;
use function Hyperf\Support\env;

class AttachmentServiceV3
{
    #[Inject]
    protected Filesystem $filesystem;

    #[Inject]
    private ClientFactory $clientFactory;



    const VIDEOS = [
        "video/webm"      => 'webm',
        "video/mp4"       => 'mp4',
        "video/quicktime" => 'mov',
        "video/ogg"       => 'ogg',
    ];

    const IMAGES = [
        "image/jpeg" => 'jpeg',
        "image/png"  => 'png',
        "image/gif"  => 'gif',
        "image/heic" => 'heic',
        "image/heif" => 'heic',
        "image/webp" => 'webp',
    ];

    const MIMES = self::VIDEOS + self::IMAGES;

    public function __construct(ContainerInterface $container)
    {
    }

    #[ExecTimeLogger('attachment_download', logParams: ['path'])]
    public function upload($file, $filename, $path = '/attachments/remote/origin/')
    {
        $stream = fopen($file, 'r+');
        $this->filesystem->writeStream(
            $location = $path . date('Y-m-d') . "/$filename",
            $stream
        );
        // swoole auto closed ???
        // defer(fn() => var_dump(gettype($stream)));
        return env('ATTACHMENT_PREFIX') . $location;
    }


    public static function getUa($url)
    {
        $url = parse_url($url);
        if ($url['host'] === 'pbs.twimg.com' || $url['host'] === 't.co') {
            return 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.104 Safari/537.36';
        }
        return ActivitypubService::getUa();
    }
    public function download($url)
    {
        $client = $this->clientFactory->create([
            'headers' => [
                'User-Agent' => self::getUa($url),
            ],
        ]);
        $tmpFile = tempnam(sys_get_temp_dir(), 'remote-download');
        $response = $client->get($url, [
            RequestOptions::SINK => $tmpFile
        ]);

        $contentType = $response->getHeaderLine('content-type');
        if (!isset(self::MIMES[$contentType])) {
            throw new AttachmentDownloadException("ContentType {$contentType} not supported");
        }

        $ext = self::MIMES[$contentType];
        defer(fn () => unlink($tmpFile));
        return [$tmpFile, $ext];
    }

    public function downloadAttachmentAndGetInfo($url)
    {
        [$file, $ext] = $this->download($url);

        return array_merge(
            $this->uploadAttachmentAndGetInfo($file, $ext),
            ['remote_url' => $url],
        );
    }
    public function uploadAttachmentAndGetInfo($file, $ext)
    {
        if (in_array(strtolower($ext), self::VIDEOS)) {
            return $this->uploadVideo($file, $ext);
        }

        if (in_array(strtolower($ext), self::IMAGES) || in_array($ext, ['jpg'])) {
            return $this->uploadImage($file, $ext);
        }
    }

    #[ExecTimeLogger('attachment_download', logParams: true)]
    public function donwloadAndUpload($url)
    {
        [$file, $ext] = $this->download($url);
        return $this->upload($file, $this->generateFilename($ext), '/remote/assets/');
    }


    #[ExecTimeLogger('attachment_download')]
    public function uploadImage($file, $ext)
    {
        $filename = $this->generateFilename($ext);

        // origin image
        $imageHandle = new ImageHandler($file, $ext);


        $origin = $imageHandle->getInfo();
        $thumb = $imageHandle->resizeThumb(550);

        $thumbnailUrl = $this->upload($thumb->file, $filename, '/attachments/remote/thmbnail/');
        $url = $this->upload($file, $filename, '/attachments/remote/origin/');

        return [
            'remote_url'          => $url,
            'url'                 => $url,
            'thumbnail_url'       => $thumbnailUrl,
            'file_type'           => Attachment::FILE_TYPE_IMAGE,
            'media_type'          => mime_content_type($file),
            'blurhash'            => $thumb->blurhash,
            'width'               => $origin->width,
            'height'              => $origin->height,
            'file_size'           => $origin->filesize,
            'thumbnail_file_size' => $thumb->filesize,
            'thumbnail_width'     => $thumb->width,
            'thumbnail_height'    => $thumb->height,
            'status' => Attachment::STATUS_FINISH,
        ];
    }

    public function uploadVideo($file, $ext)
    {
        $filename = $this->generateFilename($ext);

        $imageHandle = new ImageHandler($file, $ext);
        $thumbnail = $imageHandle->generateVideoPoster();

        $thumbnailUrl = $this->upload($thumbnail->file, $this->generateFilename("jpeg"));
        $url = $this->upload($file, $filename);

        return [
            'remote_url'          => $url,
            'url'                 => $url,
            'thumbnail_url'       => $thumbnailUrl,
            'file_type'           => Attachment::FILE_TYPE_VIDEO,
            'media_type'          => mime_content_type($file),
            'blurhash'            => null,
            'width'               => null,
            'height'              => null,
            'file_size'           => filesize($file),
            'thumbnail_file_size' => $thumbnail->filesize,
            'thumbnail_width'     => $thumbnail->width,
            'thumbnail_height'    => $thumbnail->height,
            'status' => Attachment::STATUS_FINISH,
        ];
    }

    #[NsqQueueMessage]
    public function attachmentDownload($id)
    {
        $attachment = Attachment::findOrFail($id);
        $this->remoteDownload($attachment);
    }

    #[ExecTimeLogger('remote_attachment_download')]
    public function remoteDownload(Attachment $attachment)
    {
        return $attachment->update(
            $this->downloadAttachmentAndGetInfo($attachment->remote_url)
        );
    }




    public static function generateFilename($ext)
    {
        return Str::random(20) . "." . $ext;
    }
}
