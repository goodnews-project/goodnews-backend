<?php

declare(strict_types=1);

namespace App\Controller\Admin;
use App\Model\Filter;
use App\Request\FilterRequest;
use Hyperf\Swagger\Annotation as OA;
use App\Controller\AbstractController;
use Carbon\Carbon;

#[OA\HyperfServer('http')]
class FilterController extends AbstractController
{

    #[OA\Get('/_api/admin/filters', summary:"过滤器列表", tags:["admin", "filter"])]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    public function index()
    {
        return $this->response->json(Filter::distinctCountStatus()->get());
    }

    #[OA\Post('/_api/admin/filters/create', summary:"添加/编辑 过滤器", tags:["admin", "filter"])]
    #[OA\Parameter(name: 'id', description: '过滤器ID（编辑时需要）', in : 'query')]
    #[OA\Parameter(name: 'title', description: '标题', in : 'query')]
    #[OA\Parameter(name: 'expires_in', description: '失效时间，单位秒', in: 'query', example: '如一小时传3600')]
    #[OA\Parameter(name: 'context', description: '过滤环境', in : 'query', example: '1：主页时间轴 2：通知 3：公共时间轴 4：对话 5：个人资料 多个传[1,2,3,4,5]')]
    #[OA\Parameter(name: 'act', description: 'filter action', in : 'query', example: '1:隐藏时显示警告信息 2:完全隐藏')]
    #[OA\Parameter(name: 'kw_attr', description: '关键词 key：第几个关键词(可选，编辑时需要传)，whole_word:整词 destroy:删除', in : 'query', example: '[{"key":1,"kw":"sdf","whole_word":false,"destroy":false}]')]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    public function create(FilterRequest $filterRequest)
    {
        $payload = $filterRequest->validated();
        $id = $payload['id'] ?? null;
        $data = [
            'title' => $payload['title'],
            'expired_at' => $payload['expires_in'] ? Carbon::now()->addSeconds($payload['expires_in']) : null,
            'expires_in' => $payload['expires_in'] ?? 0,
            'context' => $payload['context'],
            'act' => $payload['act'],
            'kw_attr' => \Hyperf\Collection\collect($payload['kw_attr'])->map(function ($v, $k) use ($id) {
                $v['key'] = $id && !empty($v['key']) ? $v['key'] : $k + 1;
                return $v;
            }),
        ];
        if ($id) {
            $filter = Filter::findOrFail($id);
            $filter->fill($data)->save();
            return $this->response->raw(null);
        }

        Filter::create($data);
        return $this->response->raw(null);
    }

    #[OA\Delete('/_api/admin/filters/delete/{id}', summary:"删除 过滤器", tags:["admin", "filter"])]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    public function delete($id)
    {
        $filter = Filter::findOrFail($id);
        $filter->delete();
        return $this->response->raw(null);
    }

    #[OA\Get('/_api/admin/filters/{id}', summary:"获取过滤器", tags:["admin", "filter"])]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    public function get($id)
    {
        $filter = Filter::findOrFail($id);
        return $this->response->raw($filter);
    }
}
