<?php

declare(strict_types=1);

namespace App\Controller;

use App\Middleware\AuthMiddleware;
use App\Model\ListAccount;
use App\Model\ListModel;
use App\Request\ListRequest;
use App\Service\Auth;
use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]
#[Middleware(AuthMiddleware::class)]
class ListController extends AbstractController
{
    #[OA\Get(path:'/_api/v1/list',summary:"列表",tags:['列表'])]
    public function index()
    {
        $account = Auth::account();
        $list = ListModel::where('account_id', $account['id'])->latest()->paginate(20);
        return $this->response->json($list);
    }
    #[OA\Post(path:'/_api/v1/list',summary:"创建列表",tags:['列表'])]
    #[OA\Parameter(name: 'title', description: '列表名称', in : 'body', required: true)]
    public function create(ListRequest $request)
    {
        $payload = $request->validated();
        $account = Auth::account();
        ListModel::create(array_merge($payload, [
            'account_id' => $account['id']
        ]));
        return $this->response->raw(null)->withStatus(201);
    }
    #[OA\Get(path:'/_api/v1/list/{id}',summary:"获取列表详情",tags:['列表'])] 
    public function show($id)
    {
        $account = Auth::account();
        return ListModel::where('account_id', $account['id'])->findOrFail($id);
    }

    #[OA\Delete(path:'/_api/v1/list/{id}',summary:"删除列表",tags:['列表'])] 
    public function destory($id)
    {
        $account = Auth::account();
        Db::transaction(function () use ($id,$account) {
            ListModel::where('account_id', $account['id'])->where('id',$id)->delete();
            ListAccount::where('list_id', $id)->delete();
        });
        return $this->response->raw(null)->withStatus(201);
    }
    #[OA\Put(path:'/_api/v1/list/{id}',summary:"更新列表",tags:['列表'])] 
    #[OA\Parameter(name: 'title', description: '列表名称', in : 'body', required: true)]
    public function update($id,ListRequest $request)
    {
        $payload = $request->validated();
        $account = Auth::account();
        ListModel::where('account_id', $account['id'])->findOrFail($id)->update($payload);
        return $this->response->raw(null)->withStatus(201);
    }
}
