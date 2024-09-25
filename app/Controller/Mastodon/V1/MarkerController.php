<?php

namespace App\Controller\Mastodon\V1;
use App\Controller\AbstractController;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]
class MarkerController extends AbstractController
{
    #[OA\Get(path:'/api/v1/markers',
    description: 'Get saved timeline positions',
    summary:'https://docs.joinmastodon.org/methods/markers/#get', tags:['mastodon'])]
    public function index()
    {
        return [];
    }
}
