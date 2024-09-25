<?php

declare(strict_types=1);

namespace App\Controller\Mastodon\V1;
use App\Controller\AbstractController;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]
class ListsController extends AbstractController
{
    #[OA\Get(path:"/api/v1/lists")]
    public function index()
    {
        return $this->response->json([]);
    }
}
