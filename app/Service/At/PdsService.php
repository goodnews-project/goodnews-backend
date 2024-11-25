<?php

namespace App\Service\At;

use App\Exception\AppException;
use App\Exception\PdsException;
use App\Model\Account;
use App\Model\Status;
use App\Protocol\At\Pds;
use App\Resource\At\Pds\ProfileResource;
use App\Service\AccountService;
use App\Service\StatusesService;
use Hyperf\Di\Annotation\Inject;
use Qbhy\HyperfAuth\AuthManager;

class PdsService
{

    #[Inject]
    protected AuthManager $auth;

    #[Inject]
    protected StatusesService $statusesService;

    #[Inject]
    protected AccountService $accountService;

    #[Inject]
    protected Pds $pds;

    public function getAccountByHandle($handle)
    {
        return $this->accountService->getAccountByHandle($handle);
    }

    public function createSession($identifier, $password)
    {
        if (str_starts_with($identifier, '@')) {
            $account = $this->getAccountByHandle(substr($identifier, 1));
        } elseif (str_starts_with($identifier, 'did')) {
            $account = $this->accountService->getAccountByDid($identifier);
        } else {
            throw new AppException('identifier not exists', 403);
        }

        $user = $account->user;
        if (!password_verify($password, $user->encrypted_password)) {
            throw new AppException('Wrong identifier or password', 403);
        }

        if (!$user->confirmed_at) {
            throw new AppException('请先激活账户', 403);
        }

        $token = $this->auth->login($user);

        return [
            'accessJwt' => $token,
            'refreshJwt' => $token,
        ];

    }

    public function refreshSession($refreshJwt)
    {
        return $this->auth->refresh($refreshJwt);
    }

    public function searchActorsTypeahead($term, $limit)
    {
        return Account::where('handle', 'like', '%'.$term.'%')
            ->orWhere('display_name', 'like', '%'.$term.'%')
            ->limit($limit)
            ->get();
    }

    public function feedPost($authId, $data)
    {
        $text = $data['text'];
        $createdAt = $data['createdAt'];

        $account = Account::findOrFail($authId);
        $did = $account->did;
        $cid = hash('sha256', $text . $createdAt . $did);
        $uri = "at://$did/$cid";

        $this->statusesService->create($account->id, $text, ['published_at' => $createdAt, 'uri' => $uri, 'cid' => $cid]);
        return compact('uri', 'cid');
    }

    public function getAuthorFeed($author, $cursor, $limit = 30)
    {
        $did = $this->pds->resolveAuthorDID($author);
        if (empty($did)) {
            throw new PdsException('Author not found');
        }

        $account = $this->accountService->getAccountByDid($did);

        $q = Status::where('account_id', $account->id);
        if ($cursor) {
            $q->where('id', '<', $cursor);
        }

        return $q->latest('id')->get();
    }

    public function getProfile($did)
    {
        $account = $this->accountService->getAccountByDid($did);
        return ProfileResource::make($account);
    }


}