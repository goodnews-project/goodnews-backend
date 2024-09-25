<?php

declare(strict_types=1);

namespace App\Controller\View;

use App\Controller\AbstractController;
use App\Service\TimelineService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;

use function Hyperf\ViewEngine\view;

class IndexController extends AbstractController
{
    #[Inject]
    protected TimelineService $timelineService;
    public function index()
    {
        $statuses = $this->timelineService->index();
        return view('index',compact('statuses'));
    }
}
