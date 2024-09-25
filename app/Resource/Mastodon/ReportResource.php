<?php

namespace App\Resource\Mastodon;

use App\Model\Account;
use App\Model\Report;
use Hyperf\Resource\Json\JsonResource;

class ReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        if (!$this->resource instanceof Report) {
            return [];
        }
        $report = $this->resource;
        return [
            'id' => (string) $report->id,
            'action_taken' => false,
            'action_taken_at' => null,
            'category' => (string) $report->category,
            'comment' => (string) $report->comment,
            'forwarded' => (bool) $report->forward,
            'created_at' => $report->created_at->toIso8601String(),
            'status_ids' => array_map( function ($v) {
                return (string) $v;
            }, $report->status_ids),
            'rule_ids' => $report->rule_ids ? array_map(function ($v) {
                return (string) $v;
            }, $report->rule_ids) : null,
            'target_account' => AccountResource::make($report->targetAccount)
        ];
    }
}
