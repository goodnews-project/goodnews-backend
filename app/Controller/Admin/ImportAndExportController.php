<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Middleware\AuthMiddleware;
use App\Model\Account;
use App\Model\Export;
use App\Model\Import;
use App\Model\Status;
use App\Request\ImportRequest;
use App\Service\Auth;
use App\Service\ImportAndExportService;
use Carbon\Carbon;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;
use League\Flysystem\Filesystem;
use function Hyperf\Translation\trans;

#[OA\HyperfServer('http')]
class ImportAndExportController extends AbstractController
{
    #[Inject]
    protected ImportAndExportService $importAndExportService;

    #[Inject]
    protected Filesystem $filesystem;

    #[OA\Get('/_api/admin/exports',summary:'导出列表',tags:['admin','导入导出'])]
    #[Middleware(AuthMiddleware::class)]
    public function exports()
    {
        $accountId = Auth::account()['id'];
        $account = Account::findOrFail($accountId);
        $status = Status::with(['attachments:id,file_size'])->where('account_id', $accountId)->get();
        $media = 0;
        foreach ($status as $v) {
            $media += $v->attachments->reduce(function ($media, $attachment) {
                $media += $attachment->file_size;
                return $media;
            });

        }

        $baseUrl = getApHostUrl().'/_api/admin/exports/';
        $statistics = [
            ['name' => '媒体文件存储', 'stat' => $media, 'link' => '', 'is_file_stat' => true],
            ['name' => '嘟文', 'stat' => $status->count(), 'link' => ''],
            ['name' => '正在关注', 'stat' => $account->following_count, 'link' => $baseUrl.'follows.csv'],
            ['name' => '关注者', 'stat' => $account->followers_count, 'link' => ''],
            ['name' => '屏蔽的用户', 'stat' => $account->blocks->count(), 'link' => $baseUrl.'blocks.csv'],
            ['name' => '已被你隐藏的', 'stat' => $account->mutes->count(), 'link' => $baseUrl.'mutes.csv'],
            ['name' => '书签', 'stat' => $account->bookmarks->count(), 'link' => $baseUrl.'bookmarks.csv'],
        ];

        $exports = Export::where('account_id', $accountId)->latest()->get();
        $showRequestBtn = $this->importAndExportService->showRequestBtn($accountId);
        return compact('statistics', 'exports', 'showRequestBtn');
    }

    #[OA\Post('/_api/admin/imports/upload',summary:'上传',tags:['admin','导入导出'])]
    #[OA\Parameter(name: 'type', description: '导入类型：1关注列表 2书签 3列表 4隐藏列表 5屏蔽列表 6域名屏蔽列表', in : 'query')]
    #[OA\Parameter(name: 'file', description: '文件二进制数据', in : 'query')]
    #[OA\Parameter(name: 'mode', description: '模式 1合并 2覆盖', in : 'query')]
    #[Middleware(AuthMiddleware::class)]
    public function upload(ImportRequest $importRequest)
    {
        $authAccountId = Auth::account()['id'];
        $payload = $importRequest->validated();
        $file = $payload['file'];

        $clientFilename = $file->getClientFilename();

        $res = $file->getStream()->detach();
        $i = 0;
        $datas = [];
        $header = null;
        while ($data = fgetcsv($res)) {
            $i++;
            if ($i == 1) {
                $header = $data;
            }
            if ($i <= 1 && !in_array($payload['type'], [Import::TYPE_BLOCK, Import::TYPE_BOOKMARK])) {
                continue;
            }
            $datas[] = $data;
        }

        if (empty($datas)) {
            return $this->response->json(['msg' => trans('message.admin.CSV_file_is_empty')])->withStatus(403);
        }

        if (!$this->importAndExportService->guessedType($header, $clientFilename)) {
            return $this->response->json(['msg' => trans('message.admin.import_type_not_match')])->withStatus(403);
        }

        $m = ImportAndExportService::ACTION_MAP[$payload['type']];
        if ($m && method_exists($this->importAndExportService, $m)) {
            call_user_func([$this->importAndExportService, $m], $authAccountId, $datas, $payload['mode']);
        }

        $payload['account_id'] = $authAccountId;
        unset($payload['file']);
        return Import::create($payload);
    }

    #[OA\Get('/_api/admin/exports/request',summary:'导出请求',tags:['admin','导入导出'])]
    #[Middleware(AuthMiddleware::class)]
    public function request()
    {
        $this->importAndExportService->exportRequest(Auth::account()['id']);
        return $this->response->raw(null);
    }

    #[OA\Get('/_api/admin/exports/{filename}',summary:'下载csv',tags:['admin','导入导出'])]
    #[Middleware(AuthMiddleware::class)]
    public function exportCsvByFilename($filename)
    {
        if (empty(ImportAndExportService::DOWNLOAD_MAP[$filename])) {
            return $this->response->json(['msg' => trans('admin.invalid_filename')]);
        }

        $m = ImportAndExportService::DOWNLOAD_MAP[$filename];
        $authAccountId = Auth::account()['id'];

        if (!method_exists($this->importAndExportService, $m)) {
            return $this->response->json(['msg' => trans('admin.invalid_filename')]);
        }

        [$header, $datas] = call_user_func([$this->importAndExportService, $m], $authAccountId);

        $response = $this->response;
        $response = $response->withHeader('Content-Type', 'text/csv');
        $response = $response->withHeader('Content-Disposition', "attachment; filename={$filename}");

        $outputStream = fopen('php://output', 'w');
        ob_start();
        if ($header) {
            fputcsv($outputStream, $header);
        }

        foreach ($datas as $data) {
            fputcsv($outputStream, $data);
        }
        $content = ob_get_clean();
        fclose($outputStream);

        return $response->raw($content);
    }

    #[OA\Get('/_api/admin/imports',summary:'导入列表',tags:['admin','导入导出'])]
    #[Middleware(AuthMiddleware::class)]
    public function imports()
    {
        return Import::where('account_id', Auth::account()['id'])->latest()->get();
    }

    #[OA\Get('/_api/admin/backups/{id}/download',summary:'下载你的存档',tags:['admin','导入导出'])]
    public function download($id)
    {
        $export = Export::findOrFail($id);
        $s3Url = $this->filesystem->temporaryUrl(str_replace(\Hyperf\Support\env('ATTACHMENT_PREFIX'), '', $export->file_url), Carbon::now()->addDays(7));
        return $this->response->redirect($s3Url);
    }

}
