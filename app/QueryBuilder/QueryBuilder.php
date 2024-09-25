<?php

namespace App\QueryBuilder;

use Hyperf\Database\Model\Builder;
use Hyperf\Stringable\Str;

class QueryBuilder
{
    public Builder $query;
    public array $input = [];

    public function setup(array $input, $query)
    {
        $this->query = $query;
        $this->input = $input;
    }

    public function setInput(array $input)
    {
       $this->input = $input;
       return $this;
    }

    

    public function handle($query)
    {
        if($query){
            $this->query = $query;
        }
        foreach ($this->input as $key => $val) {
            $method = $this->getQueryMethod($key);
            if (method_exists($this, $method)) {
                $this->{$method}($val);
            }
        }
        return $this->query;
    }


    public function getQueryMethod($input)
    {
        return Str::camel($input);
    }
}
