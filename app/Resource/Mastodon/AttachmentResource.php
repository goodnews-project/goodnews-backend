<?php

namespace App\Resource\Mastodon;

use App\Model\Attachment;
use App\Util\Media\Blurhash;
use Hyperf\Resource\Json\JsonResource;

class AttachmentResource extends JsonResource
{
    public ?string $wrap = null;
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        if (!$this->resource instanceof Attachment) {
            return [];
        }

        $attachment = $this->resource;
        return [
            'id' => strval($attachment->id),
            'type' => $attachment->media_type ? strtolower(explode('/', $attachment->media_type)[0]) : 'unknown',
            'url' => (string) $attachment->url,
            'preview_url' => (string) $attachment->url,
            'remote_url' => null,
            'text_url' => (string) $attachment->url,
            'meta' => $this->getMeta($attachment),
            'description' =>(string) $attachment->name,
            'blurhash' => $attachment->blurhash ?? Blurhash::defaultHash,
        ];
    }

    public function getMeta(Attachment $attachment)
    {
        $meta = [];
        switch ($attachment->file_type) {
            case Attachment::FILE_TYPE_IMAGE:
                $meta['original'] = $this->getAttrByWidthAndHeight($attachment->width, $attachment->height);
                $meta['small'] = $this->getAttrByWidthAndHeight($attachment->thumbnail_width, $attachment->thumbnail_height);
                [$x, $y] = $attachment->focus ? explode(',', $attachment->focus) : [0, 0];
                $meta['focus'] = ['x' => doubleval($x), 'y' => doubleval($y)];
                break;
            case Attachment::FILE_TYPE_VIDEO:
                $meta['length'] = '0:01:28.65';
                $meta['duration'] = 88.65;
                $meta['fps'] = 24;
                $meta['size'] = '1280x720';
                $meta['width'] = 1280;
                $meta['height'] = 720;
                $meta['aspect'] = 1.77;
                $meta['audio_encode'] = 'aac (LC) (mp4a / 0x6134706D)';
                $meta['audio_bitrate'] = '44100 Hz';
                $meta['audio_channels'] = 'stereo';
                $meta['original'] = [
                    'width' => 1280,
                    'height' => 720,
                    'frame_rate' => '6159375/249269',
                    'duration' => 88.654,
                    'bitrate' => 862056,
                ];
                $meta['small'] = [
                    'width' => 1280,
                    'height' => 720,
                    'size' => '400x225',
                    'aspect' => 1.77,
                ];
                break;
            case Attachment::FILE_TYPE_AUDIO:
            case Attachment::FILE_TYPE_GIF:
                return new \StdClass();
        }
        return $meta;
    }

    public function getAttrByWidthAndHeight($width, $height)
    {
        if ($width <= 0) {
            $width = 700;
        }

        if ($height <= 0) {
            $height = 940;
        }
        return [
            'width' => $width,
            'height' => $height,
            'size' => sprintf('%sx%s', $width, $height),
            'aspect' => $width / $height,
        ];
    }
}
