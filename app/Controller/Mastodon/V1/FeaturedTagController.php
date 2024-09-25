<?php

namespace App\Controller\Mastodon\V1;
use App\Controller\AbstractController;
use Hyperf\Swagger\Annotation as OA;


#[OA\HyperfServer('http')]
class FeaturedTagController extends AbstractController
{

    #[OA\Get(path:'/api/v1/featured_tags',
        description: 'List all hashtags featured on your profile.',
        summary:'https://docs.joinmastodon.org/methods/featured_tags/#get', tags:['mastodon'])]
    public function index()
    {
        return [];
    }
}
