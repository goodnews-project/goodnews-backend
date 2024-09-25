<?php

namespace App\Controller\Mastodon\V1;
use App\Controller\AbstractController;
use App\Model\Report;
use App\Request\Mastodon\ReportRequest;
use App\Resource\Mastodon\ReportResource;
use App\Service\AccountService;
use App\Service\Auth;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;
use Richard\HyperfPassport\PassportAuthMiddleware;

#[OA\HyperfServer('http')]
class ReportController extends AbstractController
{
    #[Inject]
    protected AccountService $accountService;

    #[OA\Post(path:'/api/v1/reports',
        description: 'File a report',
        summary:'https://docs.joinmastodon.org/methods/reports/#post', tags:['mastodon'])]
    #[Middleware(PassportAuthMiddleware::class)]
    public function index(ReportRequest $reportRequest)
    {
        $payload = $reportRequest->validated();
        $report = $this->accountService->report($payload, Auth::passport()->id);
        return ReportResource::make($report);
    }
}
