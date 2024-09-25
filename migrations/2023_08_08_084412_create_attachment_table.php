<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateAttachmentTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attachment', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tid')->index()->nullable()->comment('target table primary id');
            $table->string('from_table', 24)->nullable()->comment('target table name');
            $table->string('url')->nullable()->comment('Where can the attachment be retrieved on *this* server');
            $table->string('local_url')->nullable()->comment('本服务器或cdn附件地址');
            $table->string('name', 64)->nullable()->comment('media name');
            $table->tinyInteger('file_type')->nullable()->comment('Type of file (1:image 2:gif 3:audio 4:video)');
            $table->string('type')->nullable()->comment('Type of file');
            $table->string('media_type', 24)->nullable()->comment('media mime');
            $table->string('blurhash')->nullable()->comment('media blurhash');
            $table->integer('width')->nullable()->comment('media width');
            $table->integer('height')->nullable()->comment('media height');
            $table->datetimes();
            $table->comment('statuses_attachment table, MediaAttachment represents a user-uploaded media attachment: an image/video/audio/gif that is.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachment');
    }
}
