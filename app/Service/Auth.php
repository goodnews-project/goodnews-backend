<?php

namespace App\Service;

use App\Model\Account;
use App\Model\User;
use Hyperf\Context\ApplicationContext;
use Qbhy\HyperfAuth\AuthManager;
use \Richard\HyperfPassport\AuthManager as PassportAuth;

use function Hyperf\Support\env;

class Auth
{
    const PASSPORT_NAME = 'passport';

    public static function account(?string $token =null)
    {
        $authManager = ApplicationContext::getContainer()->get(AuthManager::class);
        if(!$authManager){
            return false;
        }

        if(!$authManager->check($token)){
            return false;
        }

        return $authManager->getPayload($token)['account'] ?? false;
    }

    public static function passport():Account|null
    {
        $authManager = ApplicationContext::getContainer()->get(PassportAuth::class);
        return $authManager->guard(self::PASSPORT_NAME)->user()?->account;
    }

    public static function passportUser():User|null
    {
        $authManager = ApplicationContext::getContainer()->get(PassportAuth::class);
        return $authManager->guard(self::PASSPORT_NAME)->user();
    }

    public static function passportClient()
    {
        $authManager = ApplicationContext::getContainer()->get(PassportAuth::class);
        return $authManager->guard(self::PASSPORT_NAME)->client();
    }
}
