<?php

namespace App\Controller\Mastodon\V1;

use App\Controller\AbstractController;
use App\Model\CustomEmoji;
use App\Resource\Mastodon\EmojiCollection;
use App\Service\Auth;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]
class CustomEmojiController extends AbstractController
{
    #[OA\Get(path:'/api/v1/custom_emojis',
    description: 'Returns custom emojis that are available on the server.',
    summary:'https://docs.joinmastodon.org/methods/custom_emojis/#get', tags:['mastodon'])]
    public function customEmojis()
    {
        return EmojiCollection::make(
            CustomEmoji::where('disabled', 0)->whereNull('domain')->get()
        );
    }
}
