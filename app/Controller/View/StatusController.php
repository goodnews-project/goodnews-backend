<?php

declare(strict_types=1);

namespace App\Controller\View;

use App\Controller\AbstractController;
use App\Model\Status;
use App\Service\StatusesService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;

use function Hyperf\ViewEngine\view;

class StatusController extends AbstractController
{
    #[Inject]
    protected StatusesService $statusesService;

    public function show($statusId)
    {
        $status = Status::withInfo()->findOrFail($statusId); 
        $replies = $this->statusesService->statusReplies($statusId);
        return view('status',compact('status','replies'));
    }
    public function card($acct,$statusId)
    {
        $status = Status::withInfo()->findOrFail($statusId);
        return view('status_card',compact('status'));
    }
}
