<?php

declare(strict_types=1);

namespace App\Controller\Mastodon\V1;

use App\Controller\AbstractController;
use App\Model\Account;

use App\Model\Follow;
use App\Model\FollowRequest;
use App\Nsq\Queue;
use App\Request\Mastodon\UpdateCredentialsRequest;
use App\Resource\Mastodon\AccountCollection;
use App\Resource\Mastodon\AccountResource;
use App\Resource\Mastodon\CredentialAccountResource;
use App\Resource\Mastodon\RelationshipCollection;
use App\Resource\Mastodon\RelationshipResource;
use App\Resource\Mastodon\StatusCollection;
use App\Service\AccountService;
use App\Service\AttachmentService;
use App\Service\Auth;
use App\Service\StatusesService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpMessage\Upload\UploadedFile;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;
use Richard\HyperfPassport\PassportAuthMiddleware;

#[OA\HyperfServer('http')]
#[Middleware(PassportAuthMiddleware::class)]
class AccountController extends AbstractController
{
    #[Inject]
    protected StatusesService $statusesService;

    #[Inject]
    protected AttachmentService $attachmentService;

    #[Inject]
    protected AccountService $accountService;

    #[OA\Get(path:'/api/v1/accounts/verify_credentials',
        description: 'Test to make sure that the user token works.',
        summary:'https://docs.joinmastodon.org/methods/accounts/#verify_credentials',tags:['mastodon'])]
    public function verifyCredentials()
    {
        $user = Auth::passportUser();
        $account = $user->account;
        if (empty($user->confirmed_at)) {
            return $this->response->json(['error' => 'Your login is missing a confirmed e-mail address'])->withStatus(403);
        }
        return CredentialAccountResource::make($account);
    }

    #[OA\Patch(path:'/api/v1/accounts/update_credentials',
        description: 'Update the user’s display and preferences.',
        summary:'https://docs.joinmastodon.org/methods/accounts/#update_credentials',tags:['mastodon'])]
    public function updateCredentials(UpdateCredentialsRequest $credentialsRequest)
    {
        $payload = $credentialsRequest->validated();
        $account = Auth::passport();
        if (isset($payload['display_name'])) {
            $account->display_name = $payload['display_name'];
        }

        if (isset($payload['note'])) {
            $account->note = $payload['note'];
        }

        if (isset($payload['avatar']) && $payload['avatar'] instanceof UploadedFile) {
            $attachment = $this->attachmentService->upload($payload['avatar'], 'image');
            $account->avatar = $attachment->url;
            $attachment->tid = $account->id;
            $attachment->from_table = $account->getMorphClass();
            $attachment->save();
        }

        if (isset($payload['header']) && $payload['header'] instanceof UploadedFile) {
            $attachment = $this->attachmentService->upload($payload['header'], 'image');
            $account->profile_image = $attachment->url;
            $attachment->tid = $account->id;
            $attachment->from_table = $account->getMorphClass();
            $attachment->save();
        }

        $account->save();

        return CredentialAccountResource::make($account);
    }

    #[OA\Get(path:'/api/v1/accounts/{id}/statuses',
        description: 'Statuses posted to the given account.',
        summary:'https://docs.joinmastodon.org/methods/accounts/#statuses',tags:['mastodon'])]
    public function statuses($id)
    {
        $account = Account::findOrFail($id);
        $type = 'with_replies';
        $excludeReplies = $this->request->input('exclude_replies');
        $onlyMedia = $this->request->input('only_media');
        $pinned = $this->request->input('pinned');
        $excludeReblogs = $this->request->input('exclude_reblogs');
        if ($excludeReplies) {
            $type = 'exclude_replies';
        } elseif ($onlyMedia) {
            $type = 'media';
        } elseif ($pinned) {
            $type = 'pinned';
        } elseif ($excludeReblogs) {
            $type = 'exclude_reblogs';
        }
        $limit = (int) $this->request->input('limit', 20);
        $limit = min($limit, 40);
        $loginAccount = Auth::passport();
        $statuses = $this->statusesService->statuses($account, $type, $loginAccount, $limit);
        return StatusCollection::make($statuses->items());
    }

    #[OA\Get(path:'/api/v1/accounts/{id}/following',
        description: 'Accounts which the given account is following, if network is not hidden by the account owner.',
        summary:'https://docs.joinmastodon.org/methods/accounts/#following', tags:['mastodon'])]
    public function following($id)
    {
        $account = Account::findOrFail($id);
        $accounts = Account::whereIn('id', $account->follows->pluck('target_account_id'))->get();
        return AccountCollection::make($accounts);
    }

    #[OA\Get(path:'/api/v1/accounts/{id}/followers',
        description: 'Accounts which follow the given account, if network is not hidden by the account owner.',
        summary:'https://docs.joinmastodon.org/methods/accounts/#followers', tags:['mastodon'])]
    public function followers($id)
    {
        $account = Account::findOrFail($id);
        $accounts = Account::whereIn('id', $account->followers->pluck('account_id'))->get();
        return AccountCollection::make($accounts);
    }

    #[OA\Get(path:'/api/v1/accounts/lookup',
        description: 'Quickly lookup a username to see if it is available, skipping WebFinger resolution.',
        summary:'https://docs.joinmastodon.org/methods/accounts/#lookup', tags:['mastodon'])]
    public function lookup()
    {
        $acct = $this->request->input('acct');
        if (empty($acct)) {
            return $this->response->json(['error' => 'acct is required'])->withStatus(403);
        }

        if (str_contains($acct, '@')) {
            [$_, $domain] = explode('@', $acct);
            if ($domain == \Hyperf\Support\env('AP_HOST')) {
                $acct = str_replace('@'.$domain, '', $acct);
            }
        }

        $account = Account::where('acct', $acct)->first();

        if (empty($account)) {
            return $this->response->json(['error' => 'Record not found'])->withStatus(404);
        }
        return AccountResource::make($account);
    }

    #[OA\Get(path:'/api/v1/accounts/relationships',
        description: 'Find out whether a given account is followed, blocked, muted, etc.',
        summary:'https://docs.joinmastodon.org/methods/accounts/#relationships',tags:['mastodon'])]
    public function relationships()
    {
        $idArr = (array) $this->request->input('id', []);
        $account = Auth::passport();
        return RelationshipCollection::make(compact('idArr', 'account'));
    }

    #[OA\Get(path:'/api/v1/accounts/{id}',
        description: 'View information about a profile.',
        summary:'https://docs.joinmastodon.org/methods/accounts/#get',tags:['mastodon'])]
    public function get($id)
    {
        $account = Account::find($id);
        if (empty($account)) {
            return $this->response->json(['error' => 'Record not found'])->withStatus(404);
        }
        return AccountResource::make($account);
    }

    #[OA\Get(path:'/api/v1/accounts/{id}/featured_tags',
        description: 'Tags featured by this account.',
        summary:'https://docs.joinmastodon.org/methods/accounts/#featured_tags', tags:['mastodon'])]
    public function featuredTags($id)
    {
        return [];
    }

    #[OA\Post(path:'/api/v1/accounts/{id}/follow',
        description: 'Follow the given account. ',
        summary:'https://docs.joinmastodon.org/methods/accounts/#follow',tags:['mastodon'])]
    public function follow($id)
    {
        $account = Auth::passport();
        try {
            $this->accountService->follow($id, $account->id);
        } catch (\Exception $e) {
            return $this->response->json(['error' => $e->getMessage()])->withStatus(403);
        }
        return RelationshipResource::make(['id' => $id, 'accountId' => $account->id]);
    }

    #[OA\Post(path:'/api/v1/accounts/{id}/unfollow',
        description: 'Unfollow the given account.',
        summary:'https://docs.joinmastodon.org/methods/accounts/#unfollow',tags:['mastodon'])]
    public function unfollow($id)
    {
        $account = Auth::passport();
        try {
            $this->accountService->unFollow($id, $account->id);
        } catch (\Exception $e) {
            return $this->response->json(['error' => $e->getMessage()])->withStatus(403);
        }
        return RelationshipResource::make(['id' => $id, 'accountId' => $account->id]);
    }

    #[OA\Post(path:'/api/v1/accounts/{id}/mute',
        description: 'Mute the given account.',
        summary:'https://docs.joinmastodon.org/methods/accounts/#mute',tags:['mastodon'])]
    public function mute($id)
    {
        $account = Auth::passport();
        $this->accountService->mute($account->id, $id);
        return RelationshipResource::make(['id' => $id, 'accountId' => $account->id]);
    }

    #[OA\Post(path:'/api/v1/accounts/{id}/unmute',
        description: 'Unmute the given account.',
        summary:'https://docs.joinmastodon.org/methods/accounts/#unmute',tags:['mastodon'])]
    public function unmute($id)
    {
        $account = Auth::passport();
        $this->accountService->unmute($account->id, $id);
        return RelationshipResource::make(['id' => $id, 'accountId' => $account->id]);
    }

    #[OA\Post(path:'/api/v1/accounts/{id}/block',
        description: 'Block the given account',
        summary:'https://docs.joinmastodon.org/methods/accounts/#block',tags:['mastodon'])]
    public function block($id)
    {
        $account = Auth::passport();
        $this->accountService->block($account->id, $id);
        return RelationshipResource::make(['id' => $id, 'accountId' => $account->id]);
    }

    #[OA\Post(path:'/api/v1/accounts/{id}/unblock',
        description: 'Unblock the given account.',
        summary:'https://docs.joinmastodon.org/methods/accounts/#unblock',tags:['mastodon'])]
    public function unblock($id)
    {
        $account = Auth::passport();
        $this->accountService->unblock($account->id, $id);
        return RelationshipResource::make(['id' => $id, 'accountId' => $account->id]);
    }

    #[OA\Get(path:'/api/v1/follow_requests',
        description: 'View pending follow requests',
        summary:'https://docs.joinmastodon.org/methods/follow_requests/#get',tags:['mastodon'])]
    public function followRequests()
    {
        $account = Auth::passport();
        $limit = $this->request->input('limit', 40);
        $accountList = $this->accountService->followRequests($account, $limit);
        return AccountCollection::make($accountList);
    }

    #[OA\Post(path:'/api/v1/follow_requests/{id}/authorize',
        description: 'Accept follow request',
        summary:'https://docs.joinmastodon.org/methods/follow_requests/#accept',tags:['mastodon'])]
    public function followRequestAccept($id)
    {
        $account = Auth::passport();
        try {
            $this->accountService->followRequestAccept($account, $id);
        } catch (\Exception $e) {
            return $this->response->json(['error' => $e->getMessage()])->withStatus(404);
        }

        return RelationshipResource::make(['id' => $id, 'accountId' => $account->id]);
    }

    #[OA\Post(path:'/api/v1/follow_requests/{id}/reject',
        description: 'Reject follow request',
        summary:'https://docs.joinmastodon.org/methods/follow_requests/#reject',tags:['mastodon'])]
    public function followRequestReject($id)
    {
        $account = Auth::passport();
        try {
            $this->accountService->followRequestReject($account, $id);
        } catch (\Exception $e) {
            return $this->response->json(['error' => $e->getMessage()])->withStatus(404);
        }

        return RelationshipResource::make(['id' => $id, 'accountId' => $account->id]);
    }
}
