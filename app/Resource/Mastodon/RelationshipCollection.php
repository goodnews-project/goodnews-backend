<?php

namespace App\Resource\Mastodon;

use App\Model\Block;
use App\Model\Follow;
use App\Model\Mute;
use Hyperf\Resource\Json\ResourceCollection;

class RelationshipCollection extends ResourceCollection
{
    public ?string $wrap = null;
    /**
     * Transform the resource collection into an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $idArr = $this->collection->get('idArr');
        $account = $this->collection->get('account');
        $accountId = $account->id;
        $data = [];
        foreach ($idArr as $id) {
            $tmp = RelationshipResource::make(compact('id', 'accountId'))->toArray();
            $data[] = $tmp;
        }
        return $data;
    }
}
