<?php

declare(strict_types=1);

namespace App\Controller\View;

use App\Controller\AbstractController;
use App\Model\Account;
use App\Service\AccountService;
use App\Service\StatusesService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;

use function Hyperf\ViewEngine\view;

class AccountController extends AbstractController
{
    #[Inject]
    protected AccountService $accountService;

    #[Inject]
    protected StatusesService $statusesService;

    public function show($acct)
    {
        $account = $this->accountService->details($acct)['account'];
        $statuses = $this->statusesService->statuses($account);
        return view('account',compact('account','statuses'));
    }
}
