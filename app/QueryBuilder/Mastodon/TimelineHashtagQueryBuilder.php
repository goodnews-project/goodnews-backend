<?php

namespace App\QueryBuilder\Mastodon;

use App\QueryBuilder\QueryBuilder;

class TimelineHashtagQueryBuilder extends QueryBuilder
{
    public function any($values)
    {
        var_dump($values);
       $this->query->whereHas('hashtags',fn ($q) => $q->whereIn('name',$values)) ;
    }
}
