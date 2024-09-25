<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Model\Admin\InstanceRule;

use Hyperf\Swagger\Annotation as OA;
use function Hyperf\Translation\trans;

#[OA\HyperfServer('http')]
class RuleController extends AbstractController
{
    #[OA\Get(path:'/admin/api/rule/list',summary:'规则列表',tags:['admin', '规则'])]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    public function list()
    {
        return InstanceRule::get();
    }

    #[OA\Post(path:'/admin/api/rule/create',summary:'添加规则',tags:['admin', '规则'])]
    #[OA\Parameter(name: 'text', description: '规则描述', in : 'query', required: true)]
    #[OA\Response(
        response: 200,
        description: '',
    )]
    public function create()
    {
        $text = $this->request->input('text');
        $text = trim($text);
        if (empty($text)) {
            return $this->response->json(['msg' => trans('message.admin.rule_desc_require')])->withStatus(403);
        }
        return InstanceRule::create(['text' => $text]);
    }

    #[OA\Delete(path: '/admin/api/rule/delete/{id}', summary: '删除规则', tags: ['admin', '规则'])]
    #[OA\Response(
        response: 200,
        description: '操作成功'
    )]
    public function delete($id)
    {
        $rule = InstanceRule::findOrFail($id);
        $rule->delete();
        return $this->response->raw(null);
    }
}
