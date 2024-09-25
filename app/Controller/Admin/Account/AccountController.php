<?php

declare(strict_types=1);

namespace App\Controller\Admin\Account;

use App\Controller\AbstractController;
use App\Model\Account;
use App\Model\AccountWarning;
use App\Model\Attachment;
use App\Model\User;
use App\Service\Auth;
use Carbon\Carbon;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]
class AccountController extends AbstractController
{

    #[OA\Get('/_api/admin/accounts/{id}', summary:"获取账户信息", tags:["admin", "account"])]
    #[OA\Response(
        response: 200,
        description: '
            attachment_sum: 媒体文件数量 ，单位 KB,
            report_count: 举报数量
            target_report_count :被举报数量

            account.user.email:电子邮箱地址
            account.user.created_at:加入于
            role_id:写死未定角色
            account.user.confirmed_at：电子邮件地址状态 不为 null 为已确认
            account.user.current_signin_ip:最后一次活跃的 IP 地址:
            account.user.current_signin_at:最后一次活跃的时间

            account.user.is_disable : 0 为正常 1:为冻结
            account.sensitized_at : 不为 null 则为敏感用户
            account.silence_at : 不为 null 则为隐藏用户 
            account.suspend : 不为 null 则为封禁用户

            account_warnings.action 1:冻结 2:敏感 3.隐藏 4.封禁
        '
    )]
    public function show($id)
    {
        $account = Account::with('user')->findOrFail($id);
        $accountWarnings = AccountWarning::with(['account:id,username,acct,display_name','targetAccount:id,username,acct,display_name'])->where('target_account_id',$id)
                                            ->latest()->take(10)->get();
        $query = Attachment::join('status','status.id','=','attachment.tid')
            ->where('account_id','=',$id);
        $fileSize = $query->clone()->sum('file_size');
        $thumbnailFileSize = $query->clone()->sum('thumbnail_file_size');
                                    
        return $this->response->json([
            'account'             => $account,
            'attachment_sum'      => $fileSize + $thumbnailFileSize,
            'report_count'        => 0,
            'target_report_count' => 0,
            'account_warnings'    => $accountWarnings
        ]);
    }

}
