<?php

namespace App\Service;

use App\Model\Status;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Cache\Annotation\CacheEvict;

class StatusCacheService
{
    #[Cacheable(prefix: 'status', value: '#{id}', ttl: 86400)]
    public function getStatusById($id)
    {
        return Status::withTrashed()->findOrFail($id);
    }

    #[CacheEvict(prefix: "status", value: '#{id}')]
    public function evictStatusById($id)
    {
//        return true;
    }

    public function getStatusByIds($ids)
    {
        $items = [];
        foreach ($ids as $id) {
            $items[] = $this->getStatusById($id);
        }
        return Status::hydrate($items);
    }

}