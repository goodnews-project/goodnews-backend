<?php
namespace App\Model\Concerns;

use App\Model\Scope\StatusWaitAttachmentScope;

trait StatusWaitAttachment
{
    /**
     * Boot the soft deleting trait for a model.
     */
    public static function bootStatusWaitAttachment()
    {
        static::addGlobalScope(new StatusWaitAttachmentScope());
    }
}