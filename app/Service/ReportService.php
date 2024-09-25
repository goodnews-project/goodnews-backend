<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Account;
use App\Model\AccountWarning;
use App\Model\AdminActionLog;
use App\Model\Report;
use App\Model\Status;
use Carbon\Carbon;
use Hyperf\DbConnection\Db;

class ReportService
{
    const LOG_ACTION_RESOLVE = '%s 处理了举报 %s';
    const LOG_ACTION_DESTROY_STATUS = '%s 删除了 %s 的嘟文';
    const LOG_ACTION_SENSITIVE_ACCOUNT = '%s 将 %s 的媒体标记为敏感内容';
    const LOG_ACTION_SILENCE_ACCOUNT = '%s 隐藏了用户 %s';
    const LOG_ACTION_SUSPEND_ACCOUNT = '%s 封禁了用户 %s';
    const LOG_ACTION_REOPEN_REPORT = '%s 重开了举报 %s';
    const LOG_ACTION_UPDATE_STATUS = '%s 刷新了 %s 的嘟文';
    const LOG_ACTION_ASSIGNED_TO_SELF = '%s 接管了举报 %s';
    const LOG_ACTION_UNASSIGNED = '%s 放弃接管举报 %s';


    /*
     * status batch action
     */
    public function handleMarkAsSensitive(Report $report, $text, $currentAccount)
    {
        Db::transaction(function () use ($report, $text, $currentAccount) {
            Status::withTrashed()->findMany($report->status_ids)->each(function (Status $status) use ($currentAccount) {
                $status->is_sensitive = 1;
                $status->save();
            });

            AccountWarning::create([
                'target_account_id' => $report->targetAccount->id,
                'action'            => AccountWarning::ACTION_MARK_STATUSES_AS_SENSITIVE,
                'report_id'         => $report->id,
                'text'              => $text
            ]);

            $action = sprintf(self::LOG_ACTION_RESOLVE, $currentAccount['username'], '#'.$report->id);
            $this->logAction($currentAccount['id'], $action, Report::class, $report->id);
        });

        return true;
    }

    public function handleDelete(Report $report, $text, $currentAccount)
    {
        Db::transaction(function () use ($report, $text, $currentAccount) {
            Status::withTrashed()->findMany($report->status_ids)->each(function (Status $status) {
                $status->deleted_at = Carbon::now();
                $status->save();
                Status::where('reblog_id', $status->id)->get()->each(function (Status $status) {
                    $status->deleted_at = Carbon::now();
                    $status->save();
                });
            });

            AccountWarning::create([
                'target_account_id' => $report->targetAccount->id,
                'action'            => AccountWarning::ACTION_DELETE_STATUS,
                'report_id'         => $report->id,
                'text'              => $text
            ]);
            $action = sprintf(self::LOG_ACTION_DESTROY_STATUS, $currentAccount['username'], $report->targetAccount->username);
            $this->logAction($currentAccount['id'], $action, Account::class, $report->targetAccount->id);
        });
    }

    /*
     * account action
     */
    public function handleSilence(Report $report, $text)
    {
        $targetAccount = $report->targetAccount;
        $r = $targetAccount->update([
            'silenced_at' => Carbon::now()
        ]);
        AccountWarning::create([
            'target_account_id' => $targetAccount['id'],
            'action'            => AccountWarning::ACTION_SILENCED,
            'report_id'         => $report->id,
            'text'              => $text
        ]);
        return $r;
    }

    public function handleSuspend(Report $report, $text)
    {
        $targetAccount = $report->targetAccount;
        $r = $targetAccount->update([
            'suspended_at' => Carbon::now()
        ]);
        AccountWarning::create([
            'target_account_id' => $targetAccount['id'],
            'action'            => AccountWarning::ACTION_SUSPENDED,
            'report_id'         => $report->id,
            'text'              => $text
        ]);
        return $r;
    }

    public function resolve(Report $report)
    {
        $report->action_taken_at = Carbon::now();
        $report->action_taken_by_account_id = Auth::account()['id'];
        return $report->save();
    }

    public function unResolve(Report $report)
    {
        $report->action_taken_at = Carbon::now();
        $report->action_taken_by_account_id = Auth::account()['id'];
        return $report->save();
    }

    public function logAction($currentAccountId, $action, $targetType, $targetId)
    {
        return AdminActionLog::create([
            'account_id' => $currentAccountId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
        ]);
    }

    public function assignToSelf($id, $currentAccount)
    {
        $report = Report::findOrFail($id);
        $report->assigned_account_id = $currentAccount['id'];
        $report->save();
        $action = sprintf(self::LOG_ACTION_ASSIGNED_TO_SELF, $currentAccount['username'], '#'.$report->id);
        $this->logAction($currentAccount['id'], $action, Report::class, $report->id);
    }

    public function unAssign($id, $currentAccount)
    {
        $report = Report::findOrFail($id);
        $report->assigned_account_id = null;
        $report->save();
        $action = sprintf(self::LOG_ACTION_UNASSIGNED, $currentAccount['username'], '#'.$report->id);
        $this->logAction($currentAccount['id'], $action, Report::class, $report->id);
    }
}