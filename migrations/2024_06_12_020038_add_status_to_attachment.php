<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class AddStatusToAttachment extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attachment', function (Blueprint $table) {
            $table->unsignedTinyInteger('status')->after('tid')->default(1)->comment('附件下载状态');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attachment', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
