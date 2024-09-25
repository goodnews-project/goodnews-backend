<?php
namespace App\Resource\Mastodon\Model;

use ArrayAccess;
use Hyperf\Contract\Arrayable;
use ReflectionProperty;
use ReflectionClass;

class MastodonModel implements Arrayable {
    public function toArray():array
    {
        $r = new ReflectionClass($this);
        $properties = $r->getProperties();
        foreach($properties as $property){
            $name = $property->getName();
            $class = get_class($this);
            $propertyType = new ReflectionProperty($this,$property->getName());
            if(!$propertyType->getType()){
                var_dump("no type");
                continue;
            }
            if($propertyType->getType()->allowsNull()){
                continue;
            }
            if(!isset($this->$name)){
                throw new \Exception("field $class::$name no validated");
            }
            // 

            // $value = $property->getValue();
            // var_dump($value);
            // 
            // var_dump($propertyType->getType()->getName());
            // var_dump($property->getName());
        }

        $vars = get_object_vars($this); 
        return array_map(function ($val){
            if($val instanceof MastodonModel){
                return $val->toArray();
            }
            return $val;
        },$vars);
    }
}