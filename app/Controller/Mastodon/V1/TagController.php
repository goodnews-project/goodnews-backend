<?php

namespace App\Controller\Mastodon\V1;

use App\Controller\AbstractController;
use App\Model\Hashtag;
use App\Resource\Mastodon\TagResource;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]
class TagController extends AbstractController
{

    #[OA\Get(path:'/api/v1/tags/{id}',
        description: 'Show a hashtag and its associated information',
        summary:'https://docs.joinmastodon.org/methods/tags/#get', tags:['mastodon'])]
    public function show($id)
    {
        $tag = Hashtag::findOrFail($id);
        return TagResource::make($tag);
    }
}
