<?php

declare(strict_types=1);

namespace App\Controller\Mastodon\V1;

use App\Controller\AbstractController;
use App\Model\Poll;
use App\Model\Status;
use App\Model\StatusEdit;
use App\Nsq\Queue;
use App\Request\Mastodon\StatusRequest;
use App\Resource\Mastodon\ContextResource;
use App\Resource\Mastodon\PollResource;
use App\Resource\Mastodon\StatusEditCollection;
use App\Resource\Mastodon\StatusResource;
use App\Resource\Mastodon\StatusSource;
use App\Service\Auth;
use App\Service\StatusesService;
use App\Util\Log;
use Carbon\Carbon;
use Hyperf\Database\Model\ModelNotFoundException;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;
use Richard\HyperfPassport\PassportAuthMiddleware;

#[OA\HyperfServer('http')]
#[Middleware(PassportAuthMiddleware::class)]
class StatusController extends AbstractController
{
    #[Inject]
    private StatusesService $statusesService;

    #[OA\Get(path:"/api/v1/statuses/{id}",tags:['mastodon'])]
    public function show($id)
    {
        $status = Status::findOrFail($id);       
        return StatusResource::make($status);
    }

    #[OA\Delete(path:"/api/v1/statuses/{id}",
        description: 'Delete one of your own statuses.',
        summary: 'https://docs.joinmastodon.org/methods/statuses/#delete', tags:['mastodon'])]
    public function destroy($id)
    {
        try {
            $status = $this->statusesService->destroy(Auth::passport()->id, $id);
        } catch (\Exception $e) {
            return $this->response->json(['error' => $e->getMessage()])->withStatus(403);
        }
        return StatusResource::make($status);
    }

    #[OA\Get(path:"/api/v1/statuses/{id}/source",
        description: '编辑回显：Obtain the source properties for a status so that it can be edited.',
        summary: 'https://docs.joinmastodon.org/methods/statuses/#source', tags:['mastodon'])]
    public function source($id)
    {
        $status = Status::findOrFail($id);
        return StatusSource::make($status);
    }

    #[OA\Put(path:"/api/v1/statuses/{id}",
        description: '编辑：Edit a given status to change its text, sensitivity, media attachments, or poll. Note that editing a poll’s options will reset the votes.',
        summary: 'https://docs.joinmastodon.org/methods/statuses/#edit', tags:['mastodon'])]
    public function edit(StatusRequest $statusRequest, $id)
    {
        $payload = $statusRequest->validated();
        $status = Status::findOrFail($id);
        if (StatusEdit::where('status_id', $status->id)->count() >= 10) {
            return $this->response->json(['error' => 'You cannot edit your post more than 10 times.'])->withStatus(422);
        }
        $this->statusesService->edit($status, $payload);

        $status = Status::findOrFail($id);
        Queue::send(['id' => $id], Queue::TOPIC_STATUS_UPDATE);
        return StatusResource::make($status);
    }

    #[OA\Get(path:'/api/v1/statuses/{id}/history',
        description: 'Get all known versions of a status, including the initial and current states.',
        summary: 'https://docs.joinmastodon.org/methods/statuses/#history', tags:['mastodon'])]
    public function history($id)
    {
        $status = Status::find($id);
        if (!$status) {
            return $this->response->json(['error' => 'Record not found'])->withStatus(404);
        }

        if (!in_array($status->scope, [Status::SCOPE_PUBLIC, Status::SCOPE_UNLISTED])) {
            return $this->response->json(['error' => 'cannot access'])->withStatus(403);
        }

        if(!$status->edits->count()) {
            return [];
        }

        return StatusEditCollection::make($status->edits);
    }

    #[OA\Post(path:'/api/v1/statuses/{id}/translate',
        description: '翻译：Translate the status content into some language.',
        summary: 'https://docs.joinmastodon.org/methods/statuses/#translate', tags:['mastodon'])]
    public function translate()
    {
        return [];
    }

   #[OA\Post(path:'/api/v1/statuses',
       description: 'Publish a status with the given parameters.',
       summary: 'https://docs.joinmastodon.org/methods/statuses/#create', tags:['mastodon'])]
    public function publish(StatusRequest $statusRequest)
    {
        $payload = $statusRequest->validated();
        $status = null;
        try {
            $account = Auth::passport();
            $client = Auth::passportClient();
            Db::transaction(function () use($account,$payload,&$status,$client){
                $scopeMap = Status::SCOPE_MAP;
                $reScopeMap = array_flip($scopeMap);
                $sensitive = $payload['sensitive'] ?? null;
                if (is_string($sensitive)) {
                    $sensitive = str_replace(['false', 'true'], [0, 1], $sensitive);
                }
                $status = $this->statusesService->create($account['id'], $payload['status'], [
                    'replyToId'   => $payload['in_reply_to_id'] ?? null,
                    'isSensitive' => (int) $sensitive,
                    'commentsDisabled' => 0,
                    'whoCanReply' => 0,
                    'scope' => $reScopeMap[$payload['visibility']] ?? null,
                    'attachments' => \Hyperf\Collection\collect($payload['media_ids'] ?? [])->map(function($id) {
                        return compact('id');
                    }),
                    'poll' => $payload['poll'] ?? [],
                    'spoiler_text' => $payload['spoiler_text'] ?? null,
                    'application_id' => $client->id ?? null
                ]);
            });
            $statusId = $status['id'];
            Queue::send(['id' => $statusId], Queue::TOPIC_STATUS_CREATE);
        } catch (\Exception $e) {
            Log::warning('publish-exception:'.$e->getMessage());
            return $this->response->json(['error' => $e->getMessage()])->withStatus(403);
        }
        $status = Status::find($statusId);
        return StatusResource::make($status);
    }

    #[OA\Get(path:'/api/v1/statuses/{id}/context',
        description: 'View statuses above and below this status in the thread.',
        summary: 'https://docs.joinmastodon.org/methods/statuses/#context', tags:['mastodon'])]
    public function context($id)
    {
        $account = Auth::passport();
        [$ancestors,$descendants] = $this->statusesService->context($id,$account);
        return ContextResource::make(compact('ancestors', 'descendants'));
    }

    #[OA\Post(path:'/api/v1/statuses/{id}/reblog',
        description: 'Reshare a status on your own profile.',
        summary: 'https://docs.joinmastodon.org/methods/statuses/#boost', tags:['mastodon'])]
    public function reblog($id)
    {
        $status = $this->statusesService->reBlog(Auth::passport()->id, $id);
        return StatusResource::make($status);
    }

    #[OA\Post(path:'/api/v1/statuses/{id}/unreblog',
        description: 'Undo boost of a status',
        summary: 'https://docs.joinmastodon.org/methods/statuses/#unreblog', tags:['mastodon'])]
    public function unreblog($id)
    {
        $status = $this->statusesService->undoReBlog(Auth::passport()->id, $id);
        return StatusResource::make($status);
    }

    #[OA\Post(path:'/api/v1/statuses/{id}/favourite',
        description: 'Add a status to your favourites list.',
        summary: 'https://docs.joinmastodon.org/methods/statuses/#favourite', tags:['mastodon'])]
    public function favourite($id)
    {
        $status = $this->statusesService->fave(Auth::passport(), $id);
        return StatusResource::make($status);
    }

    #[OA\Post(path:'/api/v1/statuses/{id}/unfavourite',
        description: 'Remove a status from your favourites list.',
        summary: 'https://docs.joinmastodon.org/methods/statuses/#unfavourite', tags:['mastodon'])]
    public function unFavourite($id)
    {
        $status = $this->statusesService->unFave(Auth::passport(), $id);
        return StatusResource::make($status);
    }

    #[OA\Post(path:'/api/v1/statuses/{id}/bookmark',
        description: '添加到书签：Privately bookmark a status.',
        summary: 'https://docs.joinmastodon.org/methods/statuses/#bookmark', tags:['mastodon'])]
    public function bookmark($id)
    {
        try {
            $status = $this->statusesService->bookmark(Auth::passport()->id, $id);
        } catch (ModelNotFoundException $notFoundException) {
            return $this->response->json(['error' => 'Record not found'])->withStatus(404);
        } catch (\Exception $e) {
            return $this->response->json(['error' => $e->getMessage()])->withStatus(500);
        }

        return StatusResource::make($status);
    }

    #[OA\Post(path:'/api/v1/statuses/{id}/unbookmark',
        description: '删除书签：Remove a status from your private bookmarks.',
        summary: 'https://docs.joinmastodon.org/methods/statuses/#unbookmark', tags:['mastodon'])]
    public function unBookmark($id)
    {
        try {
            $status = $this->statusesService->unBookmark(Auth::passport()->id, $id);
        } catch (ModelNotFoundException $notFoundException) {
            return $this->response->json(['error' => 'Record not found'])->withStatus(404);
        } catch (\Exception $e) {
            return $this->response->json(['error' => $e->getMessage()])->withStatus(500);
        }

        return StatusResource::make($status);
    }

    #[OA\Post(path:'/api/v1/polls/{id}/votes',
        description: '投票：Vote on a poll',
        summary:'https://docs.joinmastodon.org/methods/polls/#vote',tags:['mastodon'])]
    public function vote($id)
    {
        $choices = $this->request->input('choices');
        if (empty($choices) || !is_array($choices)) {
            return $this->response->json(['error' => '必须选一个'])->withStatus(422);
        }

        $authAccountId = Auth::passport()->id;
        $poll = Poll::find($id);
        if (empty($poll)) {
            return $this->response->json(['error' => 'Record not found'])->withStatus(404);
        }

        if (Carbon::now()->gt($poll->expires_at)) {
            return $this->response->json(['error' => 'Poll expired.'])->withStatus(422);
        }

        if($poll->multiple) {
            try {
                $this->statusesService->voteMultipleChoice($poll, $authAccountId, $choices);
            } catch (\Exception $e) {
                return $this->response->json(['error' => $e->getMessage()])->withStatus(422);
            }
            return PollResource::make($poll);
        }

        try {
            $this->statusesService->voteSingleChoice($poll, $authAccountId, $choices[0]);
        } catch (\Exception $e) {
            return $this->response->json(['error' => $e->getMessage()])->withStatus(422);
        }
        return PollResource::make($poll);
    }

    #[OA\Get(path:'/api/v1/polls/{id}',
        description: '刷新投票：View a poll',
        summary:'https://docs.joinmastodon.org/methods/polls/#get',tags:['mastodon'])]
    public function getPoll($id)
    {
        $poll = Poll::find($id);
        if (empty($poll)) {
            return $this->response->json(['error' => 'Record not found'])->withStatus(404);
        }
        return PollResource::make($poll);
    }

    #[OA\Post(path:'/api/v1/statuses/{id}/pin',
        description: '置顶推文：Feature one of your own public statuses at the top of your profile',
        summary:'https://docs.joinmastodon.org/methods/statuses/#pin',tags:['mastodon'])]
    public function pin($id)
    {
        $status = Status::findOrFail($id);
        $status->pinned_at = Carbon::now();
        $status->save();
        return StatusResource::make($status);
    }

    #[OA\Post(path:'/api/v1/statuses/{id}/unpin',
        description: '取消置顶推文：Unfeature a status from the top of your profile.',
        summary:'https://docs.joinmastodon.org/methods/statuses/#unpin',tags:['mastodon'])]
    public function unpin($id)
    {
        $status = Status::findOrFail($id);
        $status->pinned_at = null;
        $status->save();
        return StatusResource::make($status);
    }
}
