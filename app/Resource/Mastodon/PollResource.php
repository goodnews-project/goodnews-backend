<?php

namespace App\Resource\Mastodon;

use App\Model\Poll;
use App\Model\PollVote;
use App\Model\Status;
use App\Resource\Mastodon\Model\PollModel;
use App\Service\Auth;
use Hyperf\Resource\Json\JsonResource;

class PollResource extends JsonResource
{
    public ?string $wrap = null;
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        if (!$this->resource instanceof Poll) {
            return [];
        }

        $account = Auth::passport();
        $poll = $this->resource;
        $vote = PollVote::where('poll_id', $poll->id)->where('status_id', $poll->status_id)->where('account_id', $account->id)->get();
        return [
            'id' => strval($poll->id),
            'expires_at' => $poll->expires_at?->toIso8601String(),
            'expired' => (bool) $poll->is_expires,
            'multiple' => (bool) $poll->multiple,
            'votes_count' => $poll->votes_count,
            'voters_count' => null,
            'voted' => $vote->isNotEmpty(),
            'own_votes' => $vote->pluck('choice')->toArray(),
            'options' => \Hyperf\Collection\collect($poll->poll_options)->map(function($option, $key) use($poll) {
                $tally = $poll->cached_tallies && isset($poll->cached_tallies[$key]) ? $poll->cached_tallies[$key] : 0;
                return [
                    'title' => (string) $option,
                    'votes_count' => (int) $tally
                ];
            })->toArray(),
            'emojis' => []
        ];
    }

    public function getModel()
    {
        if (!$this->resource['poll'] instanceof Poll) {
            return [];
        }

        if (!$this->resource['status'] instanceof Status) {
            return [];
        }
        $poll = $this->resource['poll'];
        $status = $this->resource['status'];
        $vote = PollVote::where('poll_id', $poll->id)->where('status_id', $status->id)->where('account_id', $status->account_id)->get();
        $pollModel = new PollModel();
        $pollModel->id = (string) $poll->id;
        $pollModel->expires_at = $poll->expires_at?->toIso8601String() ?: '';
        $pollModel->expired = (bool) $poll->is_expires;
        $pollModel->multiple = (bool) $poll->multiple;
        $pollModel->votes_count = (int) $poll->votes_count;
        $pollModel->voters_count = (int) $poll->voters_count;
        $pollModel->voted = (int) $vote->isNotEmpty();
        $pollModel->own_votes = $vote->pluck('choice')->toArray();
        $pollModel->options = \Hyperf\Collection\collect($poll->poll_options)->map(function($option, $key) use($poll) {
            $tally = $poll->cached_tallies && isset($poll->cached_tallies[$key]) ? $poll->cached_tallies[$key] : 0;
            return [
                'title' => (string) $option,
                'votes_count' => $tally
            ];
        })->toArray();
        $pollModel->emojis = [];
        return $pollModel;
    }
}
