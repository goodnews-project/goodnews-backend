<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class AddThumbnailUrlToAttachment extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attachment', function (Blueprint $table) {
            $table->string('remote_url')->nullable()->comment('远程地址')->after('local_url');
            $table->string('thumbnail_url')->nullable()->comment('缩略图片地址')->after('remote_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attachment', function (Blueprint $table) {
            $table->dropColumn('remote_url');
            $table->dropColumn('thumbnail_url');
        });
    }
}
