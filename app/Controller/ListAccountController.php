<?php

namespace App\Controller;

use App\Model\ListAccount;
use App\Model\ListModel;
use App\Service\Auth;
use Hyperf\Swagger\Annotation as OA;
use Hyperf\HttpServer\Annotation\Middleware;
use App\Middleware\AuthMiddleware;

#[OA\HyperfServer('http')]
#[Middleware(AuthMiddleware::class)]
class ListAccountController extends AbstractController
{
    #[OA\Get(path: '/_api/v1/list/{listId}/account', summary: "列表账号列表", tags: ['列表'])]
    public function index($listId)
    {
        $account = Auth::account();
        ListModel::where('account_id', $account['id'])->findOrFail($listId);
        $listAccount = ListAccount::with('account')
            ->where('list_id', $listId)
            ->paginate(20);

        return $this->response->json($listAccount);
    }

    #[OA\Delete(path: '/_api/v1/list/{listId}/account/{accountId}', summary: "删除列表账号", tags: ['列表'])]
    public function destory($listId, $accountId)
    {
        $account = Auth::account();
        ListModel::where('account_id', $account['id'])->findOrFail($listId);
        ListAccount::where([
            ['list_id', $listId],
            ['account_id', $accountId]
        ])->delete();
        return $this->response->raw(null)->withStatus(201);
    }
    #[OA\Post(path: '/_api/v1/list/{listId}/account', summary: "创建列表账号", tags: ['列表'])]
    #[OA\Parameter(name: 'account_id', description: '账号id', in : 'body', required: true)]
    public function create($listId)
    {
        $account = Auth::account();
        ListModel::where('account_id', $account['id'])->findOrFail($listId);
        ListAccount::firstOrCreate([
            'list_id' => $listId,
            'account_id' =>  $this->request->input('account_id')
        ]);
        return $this->response->raw(null)->withStatus(201);
    }
}
