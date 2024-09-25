<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class AddTargetAccountIdToStatusUnlockLog extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('status_unlock_log', function (Blueprint $table) {
            $table->unsignedBigInteger('target_account_id')->nullable()->index()->comment('解锁推文作者ID,status.account.id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('status_unlock_log', function (Blueprint $table) {
            $table->dropColumn('target_account_id');
        });
    }
}
