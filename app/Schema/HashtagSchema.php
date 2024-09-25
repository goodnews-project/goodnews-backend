<?php
declare (strict_types=1);
namespace App\Schema;

use Hyperf\Swagger\Annotation\Property;
use Hyperf\Swagger\Annotation\Schema;
use JsonSerializable;
#[Schema(title: 'HashtagSchema')]
class HashtagSchema implements JsonSerializable
{
    #[Property(property: 'id', title: '', type: 'int')]
    public ?int $id;
    #[Property(property: 'name', title: 'tag name', type: 'string')]
    public ?string $name;
    #[Property(property: 'slug', title: 'friendly name', type: 'string')]
    public ?string $slug;
    #[Property(property: 'href', title: 'tag href', type: 'string')]
    public ?string $href;
    #[Property(property: 'is_sensitive', title: '是否敏感', type: 'int')]
    public ?int $isSensitive;
    #[Property(property: 'is_banned', title: '是否被禁用', type: 'int')]
    public ?int $isBanned;
    #[Property(property: 'created_at', title: '', type: 'mixed')]
    public mixed $createdAt;
    #[Property(property: 'updated_at', title: '', type: 'mixed')]
    public mixed $updatedAt;
    public function __construct(\App\Model\Hashtag $model)
    {
        $this->id = $model->id;
        $this->name = $model->name;
        $this->slug = $model->slug;
        $this->href = $model->href;
        $this->isSensitive = $model->is_sensitive;
        $this->isBanned = $model->is_banned;
        $this->createdAt = $model->created_at;
        $this->updatedAt = $model->updated_at;
    }
    public function jsonSerialize() : mixed
    {
        return ['id' => $this->id, 'name' => $this->name, 'slug' => $this->slug, 'href' => $this->href, 'is_sensitive' => $this->isSensitive, 'is_banned' => $this->isBanned, 'created_at' => $this->createdAt, 'updated_at' => $this->updatedAt];
    }
}