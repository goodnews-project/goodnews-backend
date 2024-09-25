<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Model\Account;
use App\Model\AccountWarning;
use App\Model\Admin\InstanceRule;
use App\Model\Attachment;
use App\Model\Report;
use App\Model\ReportNote;
use App\Model\Status;
use App\Request\Admin\ReportNoteRequest;
use App\Request\Admin\ReportRequest;
use App\Service\Auth;
use App\Service\ReportService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Stringable\Str;
use Hyperf\Swagger\Annotation as OA;
use function Hyperf\Translation\trans;

#[OA\HyperfServer('http')]
class ReportController extends AbstractController
{

    #[Inject]
    protected ReportService $reportService;

    #[OA\Get('/admin/_api/reports', tags: ['admin', '举报'])]
    #[OA\Parameter(name: 'status', description: '状态 1 未处理 2 一处理', in : 'query', required: false)]
    #[OA\Parameter(name: 'source', description: '来源 0 全部 1 本站 2 远端实例', in : 'query', required: false)]
    #[OA\Parameter(name: 'domain', description: '域名', in : 'query', required: false)]
    public function index(ReportRequest $reportRequest)
    {
        $payload = $reportRequest->validated();
        $status = $payload['status'] ?? 1;
        $source = $payload['source'] ?? 0;
        $domain = $payload['domain'] ?? null;
        $q = Account::with(['reported:id,comment,target_account_id,account_id,forward,forward_to_domains,status_ids', 'reported.account:id,username,avatar']);
        if ($source > 0) {
            $source == 1 ? $q->whereNull('domain') : $q->whereNotNull('domain');
        }

        if ($domain) {
            $q->where('domain', $domain);
        }

        return $q->whereHas('reported', function ($q) use ($status) {
            $status == 1 ? $q->whereNull('action_taken_at') : $q->whereNotNull('action_taken_at');
        })->select('id', 'display_name', 'avatar', 'acct', 'suspended_at', 'sensitized_at', 'silenced_at')
            ->get()
            ->each(function ($item) {
                $item->notes = 0;
                foreach ($item->reported as $reported) {
                    $reported->status_count = $reported->status_ids ? count($reported->status_ids) : 0;
                    $reported->attachment_count = 0;
                    foreach ((array) $reported->status_ids as $status_id) {
                        $reported->attachment_count += Attachment::where('tid', $status_id)->where('from_table', Status::class)->count();
                    }
                }
            });

    }


    #[OA\Get('/admin/_api/reports/{id}/detail', summary: '查看详情', tags: ['admin', '举报'])]
    #[OA\Response(
        response: 200,
        description: 'created_at 举报时间, account 举报人信息，target_account 被举报人信息, action_taken_at 状态(null为未处理)，forward 已转发1， 
        assigned_account 已接管的监察员信息(null为未接管)，account_warning_count 既往处罚，category 类别，comment 补充道，log_action 审计日志，notes 备注记录
        '
    )]
    public function detail($id)
    {
        $report = Report::with(['account:id,username,avatar', 'targetAccount.user:id,account_id,current_signin_at','targetAccount:id,acct,created_at,status_count,followers_count,following_count,display_name,avatar,profile_image', 'assignedAccount:id,username,avatar', 'notes', 'notes.account:id,username,avatar',
            'logAction', 'logAction.account:id,username,avatar'])
            ->findOrFail($id);
        $report->statusList = $report->status_ids ? Status::withTrashed()->whereIn('id', $report->status_ids)->get() : [];
        $report->account_warning_count = AccountWarning::where('account_id', $report->targetAccount->id)->count();
        $report->ruleList = InstanceRule::all();
        return $report;
    }

    #[OA\Put('/admin/_api/reports/{id}/setCategory', summary: '设置分类', tags: ['admin', '举报'])]
    #[OA\Parameter(name: 'category', description: '分类 其他 other 垃圾信息 spam 内容违反一条或多条服务器规则 violation', in : 'query', required: true)]
    #[OA\Parameter(name: 'rule_ids', description: '设置violation时，需要', in : 'query', required: false, example: '[1,2,3]')]
    public function setCategory($id)
    {
        $report = Report::findOrFail($id);
        $category = $this->request->input('category');
        $ruIds = $this->request->input('rule_ids') ?? [];
        if (!in_array($category, ['other', 'spam', 'violation'])) {
            return $this->response->json(['msg' => trans('message.admin.category_not_settings')])->withStatus(422);
        }

        $report->category = $category;
        if ($ruIds) {
            $report->rule_ids = $ruIds;
        }
        $report->save();
        return $this->response->raw(null);
    }


    #[OA\Put('/admin/_api/reports/{id}/batch', summary: '从报告中删除', tags: ['admin', '举报'])]
    #[OA\Parameter(name: 'status_ids', description: '要删除的ID数组', in : 'query', required: false, example: '[1,2,3]')]
    public function batchDelete($id)
    {
        $report = Report::findOrFail($id);
        $statusIds = $this->request->input('status_ids') ?? [];
        if (empty($statusIds)) {
            return $this->response->json(['msg' => trans('message.admin.status_ids_miss')])->withStatus('422');
        }
        $report->status_ids = \Hyperf\Collection\collect($report->status_ids)->diff($statusIds)->all();
        $report->save();
        return $this->response->raw(null);
    }

    #[OA\Put('/admin/_api/reports/{id}/actions', summary: '操作确认 如设置为敏感内容', tags: ['admin', '举报'])]
    #[OA\Parameter(name: 'action', description: '动作 删除：delete， 标记为敏感内容：mark_as_sensitive, 隐藏：silence，封禁：suspend', in : 'query', required: true, example: 'mark_as_sensitive')]
    #[OA\Parameter(name: 'text', description: '备注', in : 'query', required: false, example: 'sfsf')]
    public function confirm($id)
    {
        $action = $this->request->input('action');
        $m = 'handle'.Str::studly($action);
        if (!method_exists($this->reportService, $m)) {
            return $this->response->json(['msg' => trans('message.admin.btn_not_exist')])->withStatus('403');
        }

        $report = Report::findOrFail($id);
        $this->reportService->resolve($report);
        $currentAccount = Auth::account();
        call_user_func([$this->reportService, $m], $report, $this->request->input('text'), $currentAccount);
        return $this->response->raw(null);
    }

    #[OA\Put('/admin/_api/reports/{id}/resolve', summary: '标记为已处理', tags: ['admin', '举报'])]
    public function resolve($id)
    {
        $currentAccount = Auth::account();
        $report = Report::findOrFail($id);
        $this->reportService->resolve($report);
        $action = sprintf(ReportService::LOG_ACTION_RESOLVE, $currentAccount['username'], '#'.$report->id);
        $this->reportService->logAction($currentAccount['id'],$action, Report::class, $report->id);
        return $this->response->raw(null);
    }

    #[OA\Put('/admin/_api/reports/{id}/reopen', summary: '标记为未处理', tags: ['admin', '举报'])]
    public function unResolve($id)
    {
        $currentAccount = Auth::account();
        $report = Report::findOrFail($id);
        $this->reportService->unResolve($report);
        $action = sprintf(ReportService::LOG_ACTION_REOPEN_REPORT, $currentAccount['username'], '#'.$report->id);
        $this->reportService->logAction($currentAccount['id'],$action, Report::class, $report->id);
        return $this->response->raw(null);
    }

    #[OA\Put('/admin/_api/reports/{id}/assign_to_self', summary: '接管', tags: ['admin', '举报'])]
    public function assignToSelf($id)
    {
        $this->reportService->assignToSelf($id, Auth::account());
        return $this->response->raw(null);
    }

    #[OA\Put('/admin/_api/reports/{id}/unassign', summary: '取消接管', tags: ['admin', '举报'])]
    public function unAssign($id)
    {
        $this->reportService->unAssign($id, Auth::account());
        return $this->response->raw(null);
    }

    #[OA\Post('/admin/_api/report_notes', summary: '添加备注', tags: ['admin', '举报'])]
    #[OA\Parameter(name: 'content', description: '备注内容', in : 'query', required: true)]
    #[OA\Parameter(name: 'report_id', description: '举报ID', in : 'query', required: true)]
    #[OA\Parameter(name: 'is_resolve', description: '是否同时处理举报 1 处理 2 未处理', in : 'query', required: false)]
    public function addReportNotes(ReportNoteRequest $reportNoteRequest)
    {
        $payload = $reportNoteRequest->validated();
        $payload['account_id'] = Auth::account()['id'];
        if (!empty($payload['is_resolve'])) {
            $payload['is_resolve'] == 1 ? $this->resolve($payload['report_id']) : $this->unResolve($payload['report_id']);
            unset($payload['is_resolve']);
        }
        return ReportNote::create($payload);
    }

    #[OA\Delete('/admin/_api/report_notes/{id}', summary: '删除备注记录', tags: ['admin', '举报'])]
    public function deleteReportNotes($id)
    {
        $reportNote = ReportNote::findOrFail($id);
        return $reportNote->delete();
    }
}
