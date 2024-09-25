<?php

namespace App\Service;

use Hyperf\Context\ApplicationContext;
use Hyperf\HttpMessage\Exception\HttpException;
use Hyperf\Redis\Redis;
use Mailgun\Mailgun;

use function Hyperf\Support\env;
use function Hyperf\ViewEngine\view;

class MailService
{
    public static function send($param)
    {
        $mg = Mailgun::create(env("MAILGUN_API_KEY"));
        return $mg->messages()->send('good.news', $param);
    }

    public static function sendReg($to,$confirmationToken)
    {
        $redis = ApplicationContext::getContainer()->get(Redis::class);
        if($redis->get("reg_email_limit:$to")){
            throw new HttpException(403,'请稍后重试');
        }
        $redis->setex("reg_email_limit:$to",60,1);
        return self::send([
            'from'    => 'reg@good.news',
            'to'      => $to,
            'subject' => '欢迎注册',
            'html'    => view('email.reg',[
                'domain' => env('AP_HOST'),
                'link'   => sprintf(
                    "%s/confirm?token=%s",
                    getApHostUrl(),
                    $confirmationToken
                )
            ])->render()
        ]);
    }

    public static function sendResetPassword($to,$resetPasswordToken)
    {
        $redis = ApplicationContext::getContainer()->get(Redis::class);
        if($redis->get("reset_pwd_email_limit:$to")){
            throw new HttpException(403,'请稍后重试');
        }
        $redis->setex("reset_pwd_email_limit:$to",60,1);
        $h = view('email.reset_password',[
            'domain' => env('AP_HOST'),
            'link'   => sprintf(
                "%s/password/reset?token=%s",
                getApHostUrl(),
                $resetPasswordToken
            )
        ])->render();
        $a = self::send([
            'from'    => 'forgot@good.news',
            'to'      => $to,
            'subject' => '重置密码申请',
            'html'    => $h
        ]); 
        var_dump($h);
        var_dump($a);
        return $a;
    }
    
}
