<?php

namespace App\Service;

use App\Model\Attachment;
use App\Nsq\NsqQueueMessage;
use App\Service\Activitypub\ActivitypubService;
use App\Util\Image\ImageStream;
use App\Util\Log;
use App\Util\Media\Blurhash;
use GuzzleHttp\RequestOptions;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;
use League\Flysystem\Filesystem;
use Hyperf\Stringable\Str;
use Hyperf\Task\Annotation\Task;
use League\Flysystem\UnableToWriteFile;
use Jcupitt\Vips\Image;
use Jcupitt\Vips\SourceCustom;
use Jcupitt\Vips\Target;
use Jcupitt\Vips\TargetCustom;
use kornrunner\Blurhash\Blurhash as BlurhashBlurhash;

use League\Flysystem\Util;
use function Hyperf\Support\env;

class AttachmentService
{
    #[Inject]
    protected Filesystem $filesystem;

    #[Inject]
    private ClientFactory $clientFactory;

    static $MIMES = [
        'video/3gpp2'                                                               => '3g2',
        'video/3gp'                                                                 => '3gp',
        'video/3gpp'                                                                => '3gp',
        'application/x-compressed'                                                  => '7zip',
        'audio/x-acc'                                                               => 'aac',
        'audio/ac3'                                                                 => 'ac3',
        'application/postscript'                                                    => 'ai',
        'audio/x-aiff'                                                              => 'aif',
        'audio/aiff'                                                                => 'aif',
        'audio/x-au'                                                                => 'au',
        'video/x-msvideo'                                                           => 'avi',
        'video/msvideo'                                                             => 'avi',
        'video/avi'                                                                 => 'avi',
        'application/x-troff-msvideo'                                               => 'avi',
        'application/macbinary'                                                     => 'bin',
        'application/mac-binary'                                                    => 'bin',
        'application/x-binary'                                                      => 'bin',
        'application/x-macbinary'                                                   => 'bin',
        'image/bmp'                                                                 => 'bmp',
        'image/x-bmp'                                                               => 'bmp',
        'image/x-bitmap'                                                            => 'bmp',
        'image/x-xbitmap'                                                           => 'bmp',
        'image/x-win-bitmap'                                                        => 'bmp',
        'image/x-windows-bmp'                                                       => 'bmp',
        'image/ms-bmp'                                                              => 'bmp',
        'image/x-ms-bmp'                                                            => 'bmp',
        'application/bmp'                                                           => 'bmp',
        'application/x-bmp'                                                         => 'bmp',
        'application/x-win-bitmap'                                                  => 'bmp',
        'application/cdr'                                                           => 'cdr',
        'application/coreldraw'                                                     => 'cdr',
        'application/x-cdr'                                                         => 'cdr',
        'application/x-coreldraw'                                                   => 'cdr',
        'image/cdr'                                                                 => 'cdr',
        'image/x-cdr'                                                               => 'cdr',
        'zz-application/zz-winassoc-cdr'                                            => 'cdr',
        'application/mac-compactpro'                                                => 'cpt',
        'application/pkix-crl'                                                      => 'crl',
        'application/pkcs-crl'                                                      => 'crl',
        'application/x-x509-ca-cert'                                                => 'crt',
        'application/pkix-cert'                                                     => 'crt',
        'text/css'                                                                  => 'css',
        'text/x-comma-separated-values'                                             => 'csv',
        'text/comma-separated-values'                                               => 'csv',
        'application/vnd.msexcel'                                                   => 'csv',
        'application/x-director'                                                    => 'dcr',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
        'application/x-dvi'                                                         => 'dvi',
        'message/rfc822'                                                            => 'eml',
        'application/x-msdownload'                                                  => 'exe',
        'video/x-f4v'                                                               => 'f4v',
        'audio/x-flac'                                                              => 'flac',
        'video/x-flv'                                                               => 'flv',
        'image/gif'                                                                 => 'gif',
        'application/gpg-keys'                                                      => 'gpg',
        'application/x-gtar'                                                        => 'gtar',
        'application/x-gzip'                                                        => 'gzip',
        'application/mac-binhex40'                                                  => 'hqx',
        'application/mac-binhex'                                                    => 'hqx',
        'application/x-binhex40'                                                    => 'hqx',
        'application/x-mac-binhex40'                                                => 'hqx',
        'text/html'                                                                 => 'html',
        'image/x-icon'                                                              => 'ico',
        'image/x-ico'                                                               => 'ico',
        'image/vnd.microsoft.icon'                                                  => 'ico',
        'text/calendar'                                                             => 'ics',
        'application/java-archive'                                                  => 'jar',
        'application/x-java-application'                                            => 'jar',
        'application/x-jar'                                                         => 'jar',
        'image/jp2'                                                                 => 'jp2',
        'video/mj2'                                                                 => 'jp2',
        'image/jpx'                                                                 => 'jp2',
        'image/jpm'                                                                 => 'jp2',
        'image/jpeg'                                                                => 'jpeg',
        'image/pjpeg'                                                               => 'jpeg',
        'application/x-javascript'                                                  => 'js',
        'application/json'                                                          => 'json',
        'text/json'                                                                 => 'json',
        'application/vnd.google-earth.kml+xml'                                      => 'kml',
        'application/vnd.google-earth.kmz'                                          => 'kmz',
        'text/x-log'                                                                => 'log',
        'audio/x-m4a'                                                               => 'm4a',
        'application/vnd.mpegurl'                                                   => 'm4u',
        'audio/midi'                                                                => 'mid',
        'application/vnd.mif'                                                       => 'mif',
        'video/quicktime'                                                           => 'mov',
        'video/x-sgi-movie'                                                         => 'movie',
        'audio/mpeg'                                                                => 'mp3',
        'audio/mpg'                                                                 => 'mp3',
        'audio/mpeg3'                                                               => 'mp3',
        'audio/mp3'                                                                 => 'mp3',
        'video/mp4'                                                                 => 'mp4',
        'video/mpeg'                                                                => 'mpeg',
        'application/oda'                                                           => 'oda',
        'application/vnd.oasis.opendocument.text'                                   => 'odt',
        'application/vnd.oasis.opendocument.spreadsheet'                            => 'ods',
        'application/vnd.oasis.opendocument.presentation'                           => 'odp',
        'audio/ogg'                                                                 => 'ogg',
        'video/ogg'                                                                 => 'ogg',
        'application/ogg'                                                           => 'ogg',
        'application/x-pkcs10'                                                      => 'p10',
        'application/pkcs10'                                                        => 'p10',
        'application/x-pkcs12'                                                      => 'p12',
        'application/x-pkcs7-signature'                                             => 'p7a',
        'application/pkcs7-mime'                                                    => 'p7c',
        'application/x-pkcs7-mime'                                                  => 'p7c',
        'application/x-pkcs7-certreqresp'                                           => 'p7r',
        'application/pkcs7-signature'                                               => 'p7s',
        'application/pdf'                                                           => 'pdf',
        'application/octet-stream'                                                  => 'pdf',
        'application/x-x509-user-cert'                                              => 'pem',
        'application/x-pem-file'                                                    => 'pem',
        'application/pgp'                                                           => 'pgp',
        'application/x-httpd-php'                                                   => 'php',
        'application/php'                                                           => 'php',
        'application/x-php'                                                         => 'php',
        'text/php'                                                                  => 'php',
        'text/x-php'                                                                => 'php',
        'application/x-httpd-php-source'                                            => 'php',
        'image/png'                                                                 => 'png',
        'image/x-png'                                                               => 'png',
        'application/powerpoint'                                                    => 'ppt',
        'application/vnd.ms-powerpoint'                                             => 'ppt',
        'application/vnd.ms-office'                                                 => 'ppt',
        'application/msword'                                                        => 'doc',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'application/x-photoshop'                                                   => 'psd',
        'image/vnd.adobe.photoshop'                                                 => 'psd',
        'audio/x-realaudio'                                                         => 'ra',
        'audio/x-pn-realaudio'                                                      => 'ram',
        'application/x-rar'                                                         => 'rar',
        'application/rar'                                                           => 'rar',
        'application/x-rar-compressed'                                              => 'rar',
        'audio/x-pn-realaudio-plugin'                                               => 'rpm',
        'application/x-pkcs7'                                                       => 'rsa',
        'text/rtf'                                                                  => 'rtf',
        'text/richtext'                                                             => 'rtx',
        'video/vnd.rn-realvideo'                                                    => 'rv',
        'application/x-stuffit'                                                     => 'sit',
        'application/smil'                                                          => 'smil',
        'text/srt'                                                                  => 'srt',
        'image/svg+xml'                                                             => 'svg',
        'application/x-shockwave-flash'                                             => 'swf',
        'application/x-tar'                                                         => 'tar',
        'application/x-gzip-compressed'                                             => 'tgz',
        'image/tiff'                                                                => 'tiff',
        'text/plain'                                                                => 'txt',
        'text/x-vcard'                                                              => 'vcf',
        'application/videolan'                                                      => 'vlc',
        'text/vtt'                                                                  => 'vtt',
        'audio/x-wav'                                                               => 'wav',
        'audio/wave'                                                                => 'wav',
        'audio/wav'                                                                 => 'wav',
        'application/wbxml'                                                         => 'wbxml',
        'video/webm'                                                                => 'webm',
        'audio/x-ms-wma'                                                            => 'wma',
        'application/wmlc'                                                          => 'wmlc',
        'video/x-ms-wmv'                                                            => 'wmv',
        'video/x-ms-asf'                                                            => 'wmv',
        'application/xhtml+xml'                                                     => 'xhtml',
        'application/excel'                                                         => 'xl',
        'application/msexcel'                                                       => 'xls',
        'application/x-msexcel'                                                     => 'xls',
        'application/x-ms-excel'                                                    => 'xls',
        'application/x-excel'                                                       => 'xls',
        'application/x-dos_ms_excel'                                                => 'xls',
        'application/xls'                                                           => 'xls',
        'application/x-xls'                                                         => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
        'application/vnd.ms-excel'                                                  => 'xlsx',
        'application/xml'                                                           => 'xml',
        'text/xml'                                                                  => 'xml',
        'text/xsl'                                                                  => 'xsl',
        'application/xspf+xml'                                                      => 'xspf',
        'application/x-compress'                                                    => 'z',
        'application/x-zip'                                                         => 'zip',
        'application/zip'                                                           => 'zip',
        'application/x-zip-compressed'                                              => 'zip',
        'application/s-compressed'                                                  => 'zip',
        'multipart/x-zip'                                                           => 'zip',
        'text/x-scriptzsh'                                                          => 'zsh',
        'image/webp'                                                                => 'webp',
    ];

    // #[Task]
    public function download($url, $thumbnailImage = false, $generateBlurhash = false)
    {
        $client = $this->clientFactory->create([
            'headers' => [
                'User-Agent'   => ActivitypubService::getUa(),
            ],
        ]);
        Log::info("start download {$url}");
        $tmpFile = tempnam(sys_get_temp_dir(), 'remote-download');
        $response = $client->get($url,[
            RequestOptions::SINK => $tmpFile
        ]);
        Log::info("end download {$url}");
        $contentType = $response->getHeaderLine('content-type');
        if(!isset(self::$MIMES[$contentType])){
            throw new \Exception(__METHOD__.' not set contentType');
        }
        $ext = self::$MIMES[$contentType];

        $filename = Str::random(20);
        $location = '/remote/'.date('Y-m-d') . "/$filename.$ext";
        $stream = fopen($tmpFile, 'r+');
        $this->filesystem->writeStream(
            $location,
            $stream
        );
        fclose($stream);

        $originImageUrl = env('ATTACHMENT_PREFIX'). $location;
        if ($thumbnailImage) {
            [$resizeTmpFile,$thumbWidth,$thumbHeight] = $this->generateThumbnail($tmpFile,$ext);

            $resizeStream = fopen($resizeTmpFile, 'r+'); 
            $thumbnailUrl = $this->uploadFilesystem($resizeStream,'remote/small-image',"{$filename}.{$ext}");

            $blurHash = null;
            if ($generateBlurhash) {
                $blurHash = Blurhash::generate($resizeTmpFile,$ext);
            }

            fclose($resizeStream);
            unlink($tmpFile);
            unlink($resizeTmpFile);

            return compact('originImageUrl', 'thumbnailUrl', 'blurHash', 'thumbWidth', 'thumbHeight');
        }

        unlink($tmpFile);
        return $originImageUrl;
    }
    public function generateThumbnail($filepath,$ext)
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'thumbnail') . ".$ext";
        $image = Image::newFromFile($filepath); 
        $image = $image->thumbnail_image(550,[
            'size' => 'down',
        ]);
        $image->writeToFile($tmpFile);
        return [$tmpFile,$image->width,$image->height];
    }

    public function upload($file,$type)
    {
        
        switch($type){
            case "image":
                return $this->uploadImage($file);
                break;
            case 'video':
                return $this->uploadVideo($file);
                break;
        }
        
    }
    public function uploadVideo($file)
    {
        $fileStream = fopen($file->getRealPath(), 'r+');
        $url = $this->uploadFilesystem($fileStream,'video',Str::random(15)."." .$file->getExtension());
        return Attachment::create([
            'url'        => $url,
            'file_type'  => Attachment::FILE_TYPE_VIDEO,
            'media_type' => $file->getMimeType()
        ]); 
    }
    public function uploadImage($file)
    {
        $filename = Str::random(20);
        $ext = $file->getExtension();

        // origin-image
        $image = Image::newFromFile($file->getRealPath());  
        $imageStream = fopen($file->getRealPath(), 'r+'); 
        $originImageUrl = $this->uploadFilesystem($imageStream,'origin-image',"{$filename}.{$ext}");

        // resize-image
        [$resizeTmpFile,$thumbWidth,$thumnbHeight] = $this->generateThumbnail($file->getRealPath(),$ext);
        $resizeStream = fopen($resizeTmpFile, 'r+'); 
        $thumbnailUrl = $this->uploadFilesystem($resizeStream,'small-image',"{$filename}.{$ext}");

        $blurhashInfo = Blurhash::generate($resizeTmpFile, $file->getMimeType());
        unlink($resizeTmpFile);
        return Attachment::create([
            'url'              => $originImageUrl,
            'thumbnail_url'    => $thumbnailUrl,
            'file_type'        => Attachment::FILE_TYPE_IMAGE,
            'media_type'       => $file->getMimeType(),
            'blurhash'         => $blurhashInfo['blurhash'],
            'width'            => $image->width,
            'height'           => $image->height,
            'thumbnail_width'  => $thumbWidth,
            'thumbnail_height' => $thumnbHeight
        ]);
    }

    public function thumbnailImage($filename, Image $originImage, ImageStream $imageStream, &$resizeStream = null)
    {
        $resizeImage = $originImage->thumbnail_image(550,[
            'size' => 'down',
        ]);
        $resizeStream = $imageStream->write($resizeImage);
        $path = $this->uploadFilesystem($resizeStream,'small-image',"{$filename}.{$imageStream->extension}");
        return [$path,$resizeImage->width,$resizeImage->height];
    }

    // public function uploadAvatar(ImageStream $imageStream)
    // {
    //     $image = $imageStream->createImage()->thumbnail_image(400,[
    //         'height' => '400',
    //         'crop'   => 'centre'
    //     ]);
    //     $stream = $imageStream->write($image);
    //     $url = $this->uploadFilesystem($stream,'avatar',$imageStream->extension);
    //     return Attachment::create([
    //         'url'        => $url,
    //         'file_type'  => Attachment::FILE_TYPE_AVATAR,
    //         'media_type' => $imageStream->file->getMimeType(),
    //         'width'      => $image->width,
    //         'height'     => $image->height,
    //     ]);
    // }

    
    public function uploadFilesystem(&$stream,string $type, string $filename)
    {
        $path = sprintf("/upload/%s/%s/%s",
            $type,
            date('Y-m-d'),
            $filename
        );
        $this->filesystem->writeStream($path,$stream);
        return env('ATTACHMENT_PREFIX'). $path;
    }

    public function localUrlToFullPath($localUrl)
    {
        return str_replace(env('ATTACHMENT_PRIEFIX'), BASE_PATH.'/public', $localUrl);
    }
}
