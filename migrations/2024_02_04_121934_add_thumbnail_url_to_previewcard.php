<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class AddThumbnailUrlToPreviewcard extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('preview_card', function (Blueprint $table) {
            $table->string('thumbnail_url')->nullable()->comment('缩略图片地址')->after('image_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('preview_card', function (Blueprint $table) {
            $table->dropColumn('thumbnail_url');
        });
    }
}
