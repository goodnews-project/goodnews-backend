<?php
declare(strict_types=1);

namespace App\Model\Scope;

use App\Model\Attachment;
use Hyperf\Database\Model\Model;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Scope;

class StatusWaitAttachmentScope implements Scope
{
    /**
     * Apply the scope to a given Model query builder.
     *
     * @param \Hyperf\Database\Model\Builder $builder
     * @param \Hyperf\Database\Model\Model $model
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->whereDoesntHave('attachments', fn ($q) => $q->where('status', '=', Attachment::STATUS_WAIT));

    }
}