<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Model\Admin\IpBlock;
use App\Request\IpBlockRequest;
use App\Util\CidrMatch;
use Carbon\Carbon;
use Hyperf\Swagger\Annotation as OA;
use function Hyperf\Translation\trans;

#[OA\HyperfServer('http')]
class IpBlockController extends AbstractController
{

    #[OA\Get('/admin/_api/ip_blocks', summary:"ip规则列表", tags:["admin", "ip规则"])]
    public function index()
    {
        return IpBlock::get();
    }

    #[OA\Delete('/admin/_api/ip_blocks/batch', summary:"ip规则批量删除", tags:["admin", "ip规则"])]
    #[OA\Parameter(name: 'ids', description: 'id 数组', in : 'query', required: true)]
    public function delete()
    {
        $ids = $this->request->input('ids', []);
        if (empty($ids)) {
            return $this->response->json(['msg' => trans('message.admin.ids_miss')])->withStatus(403);
        }
        IpBlock::whereIn('id', $ids)->get()->each(function (IpBlock $ipBlock) {
            $ipBlock->delete();
        });

        return $this->response->raw(null);
    }

    #[OA\Post('/admin/_api/ip_blocks/create', summary:"新建ip规则", tags:["admin", "ip规则"])]
    #[OA\Parameter(name: 'ip', description: 'ip', in : 'query', required: true)]
    #[OA\Parameter(name: 'expires_in', description: '失效时间，单位秒', in: 'query', example: '如一小时传3600')]
    #[OA\Parameter(name: 'severity', description: '规则 1 限制注册 2 阻止注册 3 阻止访问', in : 'query')]
    #[OA\Parameter(name: 'comment', description: '备注', in : 'query')]
    public function create(IpBlockRequest $ipBlockRequest)
    {
        $payload = $ipBlockRequest->validated();
        $payload['expires_at'] = Carbon::now()->addSeconds($payload['expires_in']);

        $cidrMatch = new CidrMatch();
        if (!str_contains($payload['ip'], '/')) {
            $defaultMask = $cidrMatch->getDefaultMaskByIp($payload['ip']);
            if (empty($defaultMask)) {
                return $this->response->json(['msg' => trans('message.admin.ip_format_error')])->withStatus(422);
            }
            $payload['ip'] .= '/'.$defaultMask;
        }

        $res = IpBlock::pluck('ip')->filter(function ($cidr) use ($payload, $cidrMatch) {
            [$ip, $mask] = explode('/', $payload['ip']);
            return $mask == substr($cidr, strpos($cidr, '/') + 1) && $cidrMatch->match($ip, $cidr);
        });
        if ($res->isNotEmpty()) {
            return $this->response->json(['msg' => trans('message.admin.ip_segment_exists')])->withStatus(422);
        }
        return IpBlock::create($payload);
    }


}
