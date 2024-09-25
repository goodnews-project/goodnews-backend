<?php

namespace App\QueryBuilder;

trait QueryBuildable
{
    public QueryBuilder $queryBuilder;
    
    public function scopeQueryBuilder($query, $queryBuilder, array $input = [])
    {
        $this->queryBuilder = new $queryBuilder();
        $this->queryBuilder->setup($this->removeEmptyInput($input), $query);
        return $this->queryBuilder->handle($query);
    }

   
}
