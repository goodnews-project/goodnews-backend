<?php

namespace App\Resource\Mastodon;

use App\Model\PreviewCard;
use App\Resource\Mastodon\Model\CardModel;
use App\Util\Media\Blurhash;
use Hyperf\Resource\Json\JsonResource;

class PreviewCardResource extends JsonResource
{
    public ?string $wrap = null;
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        if (!$this->resource instanceof PreviewCard) {
            return [];
        }

        $previewCard = $this->resource;
//        return $this->getModel()->toArray();
        $url = $previewCard->provider_name == 'Youtube' ? (string) $previewCard->provider_url : (string) $previewCard->url;
        return [
            'url' => $url,
            'title' => (string) $previewCard->title,
            'description' => (string) $previewCard->description,
            'type' => is_null($previewCard->blurhash) ? 'video' : 'photo',
            'author_name' => '',
            'author_url' => '',
            'provider_name' => (string) $previewCard->provider_name,
            'provider_url' => (string) $previewCard->provider_url,
            'html' => '<iframe width="480" height="270" src="'.$url.'" frameborder="0" allowfullscreen=""></iframe>',
            'width' => (int) $previewCard->width,
            'height' => (int) $previewCard->height,
            'image' => (string) $previewCard->image_url,
            'embed_url' => '',
            'blurhash' => $previewCard->blurhash ?: Blurhash::defaultHash,
        ];
    }

    public function getModel()
    {
        if (!$this->resource instanceof PreviewCard) {
            return [];
        }

        $previewCard = $this->resource;
        $cardModel = new CardModel();
        $cardModel->author_name = '';
        $cardModel->author_url = '';
        $cardModel->url = $previewCard->provider_name == 'Youtube' ? (string) $previewCard->provider_url : (string) $previewCard->url;
        $cardModel->title = (string) $previewCard->title;
        $cardModel->description = (string) $previewCard->description;
        $cardModel->type = is_null($previewCard->blurhash) ? 'video' : 'photo';
        $cardModel->provider_name = (string) $previewCard->provider_name;
        $cardModel->provider_url = (string) $previewCard->provider_url;
        $cardModel->html = '<iframe width="480" height="270" src="https://www.youtube.com/embed/OMv_EPMED8Y?feature=oembed" frameborder="0" allowfullscreen=""></iframe>';
        $cardModel->width = (int) $previewCard->width;
        $cardModel->height = (int) $previewCard->height;
        $cardModel->image = (string) $previewCard->image_url;
        $cardModel->embed_url = '';
        $cardModel->blurhash = $previewCard->blurhash ?: Blurhash::defaultHash;
        return $cardModel;
    }
}
