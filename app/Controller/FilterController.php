<?php

declare(strict_types=1);

namespace App\Controller;
use App\Middleware\AuthMiddleware;
use App\Model\Filter;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;
use function Hyperf\Translation\trans;

#[OA\HyperfServer('http')]
class FilterController extends AbstractController
{

    #[OA\Get('/_api/filters', summary:"过滤器列表", tags:["_api", "filter"])]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    #[Middleware(AuthMiddleware::class)]
    public function index()
    {
        return $this->response->json(Filter::distinctCountStatus()->get());
    }

    #[OA\Post('/_api/filters/create', summary:"添加 过滤器", tags:["_api", "filter"])]
    #[OA\Parameter(name: 'title', description: '过滤器标题', in : 'query', required: true)]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    #[Middleware(AuthMiddleware::class)]
    public function create()
    {
        $title = $this->request->input('title');
        if (empty($title)) {
            return $this->response->json(['msg' => trans('filter.title_cant_empty')])->withStatus(422);
        }

        $filter = Filter::create([
            'title' => $title,
            'expired_at' => null,
            'expires_in' => 0,
            'context' => array_keys(Filter::contextMap),
            'act' => Filter::ACT_WARN,
            'kw_attr' => null,
        ]);
        return $this->response->raw($filter->id);
    }
}
