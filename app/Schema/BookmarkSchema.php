<?php
declare (strict_types=1);
namespace App\Schema;

use Hyperf\Swagger\Annotation\Property;
use Hyperf\Swagger\Annotation\Schema;
use JsonSerializable;
#[Schema(title: 'BookmarkSchema')]
class BookmarkSchema implements JsonSerializable
{
    public function __construct(\App\Model\Bookmark $model)
    {
    }
    public function jsonSerialize() : mixed
    {
        return [];
    }
}