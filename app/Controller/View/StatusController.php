<?php

declare(strict_types=1);

namespace App\Controller\View;

use App\Controller\AbstractController;
use App\Model\Setting;
use App\Model\Status;
use App\Service\StatusesService;
use App\Service\ViewService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;

use function Hyperf\ViewEngine\view;

class StatusController extends AbstractController
{
    #[Inject]
    protected StatusesService $statusesService;
    #[Inject]
    protected ViewService $viewService;

    public function show($id)
    {
        $status = Status::with(['account', 'attachments'])->findOrFail($id);
        $description = mb_substr(strip_tags($status->content), 0, 250);
        $title = "{$status->account->display_name}:" . mb_substr($description, 0, 100) . '...';
        $meta = [
            'og:site_name' => Setting::whereNull('settingable_id')->where('key', 'site_title')->value('value'),
            'og:type' => 'article',
            'og:url' => $this->request->getUri(),
            'og:title' => $title,
            'og:description' => $description,
        ];

        if ($status->attachments->count()) {
            $meta['og:image'] = $status->attachments->first()->url;
        }
        $html = $this->viewService->render($title, $meta);
        return $this->response->html($html);
    }

    public function card($acct, $statusId)
    {
        $status = Status::withInfo()->findOrFail($statusId);
        return view('status_card', compact('status'));
    }
}
