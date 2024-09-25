<?php

namespace App\QueryBuilder\Mastodon;

use App\QueryBuilder\QueryBuilder;

class TimelineQueryBuilder extends QueryBuilder
{
    public function local($value)
    {
        $this->query->where('is_local',1);
    }

    public function remote($value)
    {
        $this->query->where('is_local',0); 
    }

    public function onlyMedia($value)
    {
        $this->query->whereHas('attachments');
    }
    
}
