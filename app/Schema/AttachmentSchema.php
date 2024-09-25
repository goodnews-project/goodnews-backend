<?php
declare (strict_types=1);
namespace App\Schema;

use Hyperf\Swagger\Annotation\Property;
use Hyperf\Swagger\Annotation\Schema;
use JsonSerializable;
#[Schema(title: 'AttachmentSchema')]
class AttachmentSchema implements JsonSerializable
{
    #[Property(property: 'id', title: '', type: 'int')]
    public ?int $id;
    #[Property(property: 'tid', title: 'target table primary id', type: 'int')]
    public ?int $tid;
    #[Property(property: 'from_table', title: 'target table name', type: 'string')]
    public ?string $fromTable;
    #[Property(property: 'url', title: 'Where can the attachment be retrieved on *this* server', type: 'string')]
    public ?string $url;
    #[Property(property: 'name', title: 'media name', type: 'string')]
    public ?string $name;
    #[Property(property: 'file_type', title: 'Type of file (1:image 2:gif 3:audio 4:video)', type: 'int')]
    public ?int $fileType;
    #[Property(property: 'type', title: 'Type of file', type: 'string')]
    public ?string $type;
    #[Property(property: 'media_type', title: 'media mime', type: 'string')]
    public ?string $mediaType;
    #[Property(property: 'blurhash', title: 'media mime', type: 'string')]
    public ?string $blurhash;
    #[Property(property: 'width', title: 'media width', type: 'int')]
    public ?int $width;
    #[Property(property: 'height', title: 'media height', type: 'int')]
    public ?int $height;
    #[Property(property: 'created_at', title: '', type: 'mixed')]
    public mixed $createdAt;
    #[Property(property: 'updated_at', title: '', type: 'mixed')]
    public mixed $updatedAt;
    public function __construct(\App\Model\Attachment $model)
    {
        $this->id = $model->id;
        $this->tid = $model->tid;
        $this->fromTable = $model->from_table;
        $this->url = $model->url;
        $this->name = $model->name;
        $this->fileType = $model->file_type;
        $this->type = $model->type;
        $this->mediaType = $model->media_type;
        $this->blurhash = $model->blurhash;
        $this->width = $model->width;
        $this->height = $model->height;
        $this->createdAt = $model->created_at;
        $this->updatedAt = $model->updated_at;
    }
    public function jsonSerialize() : mixed
    {
        return ['id' => $this->id, 'tid' => $this->tid, 'from_table' => $this->fromTable, 'url' => $this->url, 'name' => $this->name, 'file_type' => $this->fileType, 'type' => $this->type, 'media_type' => $this->mediaType, 'blurhash' => $this->blurhash, 'width' => $this->width, 'height' => $this->height, 'created_at' => $this->createdAt, 'updated_at' => $this->updatedAt];
    }
}