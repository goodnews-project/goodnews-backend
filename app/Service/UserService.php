<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Account;
use App\Model\EmailDomainBlock;
use App\Model\User;
use Carbon\Carbon;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;
use Qbhy\HyperfAuth\AuthManager;
use function Hyperf\Config\config;
use function Hyperf\Support\env;

class UserService
{

    const S_ACCOUNT_TOKEN_KEY = 's:token:key:accountId:%s';

    #[Inject]
    protected AuthManager $auth;

    #[Inject]
    protected Redis $redis;

    #[Inject]
    protected NotificationService $notificationService;

    protected Account $account;



    public function __construct(Account $account)
    {
        $this->account = $account;
    }

    // IsLocal returns whether account is a local user account.
    public function isLocal()
    {
        return $this->account->domain == '' || $this->account->domain == env('AP_HOST');
    }

    // IsRemote returns whether account is a remote user account.
    public function isRemote()
    {
        return !$this->isLocal();
    }

    // IsInstance returns whether account is an instance internal actor account.
    public function isInstance()
    {
        if ($this->isLocal()) {
            return $this->account->username == env('AP_HOST');
        }

        return $this->account->username == $this->account->domain ||
            $this->account->followers_uri == '' ||
            $this->account->following_uri == '' ||
            ($this->account->username == 'internal.fetch' && str_contains($this->account->note, 'internal service actor')) ||
            $this->account->username == 'instance.actor';
    }

    public function transformAccount()
    {
        $account = $this->account;
        $local = $this->isLocal();
        $acct = $local ? $account->username : substr($account->username, 1);
        $username = $local ? $account->username : explode('@', $acct)[0];

        return [
            'id'              => (string) $account->id,
            'username'        => $username,
            'acct'            => $acct,
            'display_name'    => $account->display_name,
            'followers_count' => $account->followers_count,
            'following_count' => $account->following_count,
            'note'            => $account->note,
            'note_text'       => $account->note ? strip_tags($account->note) : null,
            'url'             => $account->url,
            'avatar'          => $account->avatar_remote_url,
            'local'           => $local,
            'created_at'      => $account->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function login($email, $password)
    {
        $user = User::where('email', $email)->first();
        if (!$user) {
            throw new \Exception('邮箱或密码错误', 403);
        }

        if (!password_verify($password, $user->encrypted_password)) {
            throw new \Exception('邮箱或密码错误', 403);
        }

        if (!$user->confirmed_at) {
            throw new \Exception('请先激活账户', 403);
        }

        $token = $this->auth->login($user, [
            'account' => [
                'id' => $user->account->id,
                'acct' => $user->account->acct,
                'username' => $user->account->username,
            ]
        ]);

        $user->update([
            'current_signin_at' => Carbon::now(),
            'current_signin_ip' => getClientIp()
        ]);


        $expire = config('auth.guards.jwt.ttl');
        $this->setActive($user->id);
        $this->redis->setEx(sprintf(self::S_ACCOUNT_TOKEN_KEY, $user->account->id), $expire, $token);

        return [
            'account' => $user->account,
            'token'   => $token,
            'expire'  => $expire,
        ];
    }

    public function setActive($userId)
    {
        // 统计活跃人数
        $date = Carbon::now()->toDateString();
        $key = "active_users:{$date}";
        if (!$this->redis->exists($key)) {
            $this->redis->setBit($key, $userId, true);
            $this->redis->expire($key, 3600 * 24 * 15);
        } else {
            $this->redis->setBit($key, $userId, true);
        }
    }

    public function reg(array $payload)
    {
        if (User::where('email', $payload['email'])->exists()) {
            throw new \Exception('user already exists!');
        }

        $emailDomain = strtok($payload['email'], '@');
        if (EmailDomainBlock::where('domain', $emailDomain)->exists()) {
            throw new \Exception('email domain blocked');
        }


        $confirmationToken = password_hash($payload['email'], PASSWORD_DEFAULT);
        $user = null;
        Db::transaction(function () use ($payload, $confirmationToken, &$user) {
            $pkiConfig = [
                'digest_alg'       => 'sha512',
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ];
            $pki = openssl_pkey_new($pkiConfig);
            openssl_pkey_export($pki, $pki_private);
            $pki_public = openssl_pkey_get_details($pki);
            $pki_public = $pki_public['key'];

            $locale = $payload['locale'] ?? 'zh-CN';
            $account = Account::create([
                'username'     => $payload['username'],
                'acct'         => $payload['username'],
                'display_name' => $payload['display_name'],
                'is_activate'  => false,
                'private_key'  => $pki_private,
                'public_key'   => $pki_public,
                'language'     => $locale
            ]);

            $user = User::create([
                'account_id'         => $account->id,
                'email'              => $payload['email'],
                'encrypted_password' => password_hash($payload['password'], PASSWORD_DEFAULT),
                'signup_ip'          => $payload['clientIp'] ?? '',
                'locale'             => $locale,
                'confirmation_token' => $confirmationToken
            ]);
        });
        MailService::sendReg($payload['email'], $confirmationToken);
        return $user;
    }
    public function isEmailConfirm($email)
    {
        $user = User::where('email', $email)->first();
        if (!$user) {
            return false;
        }
        if (!$user->confirmed_at) {
            return false;
        }
        return true;
    }
    public function confirm($account, $confirmationToken)
    {
        $user = User::where('confirmation_token', $confirmationToken)
            ->with('account')
            ->first();

        if (empty($user['account']['acct'])) {
            throw new \Exception('链接已失效', 403);
        }



        if (!$user->confirmed_at) {
            $user->update([
                'confirmed_at'       => Carbon::now(),
            ]);
            $this->notificationService->userConfirm($user['account_id']);
        }



        $account = Account::where('id', $user['account_id'])
            ->first(['id', 'acct', 'username', 'display_name', 'avatar']);
        $token = $this->auth->login($user, [
            'account' => $account
        ]);
        return [
            'msg'     => '激活成功',
            'account' => $account,
            'token'   => $token,
            'expire'  => config('auth.guards.jwt.ttl'),
        ];
    }



    public function refreshToken($authorization)
    {
        [$_, $token] = explode("Bearer ", $authorization);
        $newToken = $this->auth->refresh($token);
        $userId = $this->auth->id($newToken);
        User::findOrFail($userId)->update([
            'current_signin_at' => Carbon::now(),
            'current_signin_ip' => getClientIp()
        ]);
        $this->setActive($userId);
        return [
            'token'  => $newToken,
            'expire' => config('auth.guards.jwt.ttl'),
        ];
    }
}
