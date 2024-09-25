<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class AddFileSizeToAttachment extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attachment', function (Blueprint $table) {
            $table->string('file_size')->nullable()->after('thumbnail_width');
            $table->string('thumbnail_file_size')->nullable()->after('thumbnail_width');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attachment', function (Blueprint $table) {
            $table->dropColumn(['file_size','thumbnail_file_size']);
        });
    }
}
