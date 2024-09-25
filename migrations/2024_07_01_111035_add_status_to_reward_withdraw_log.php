<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class AddStatusToRewardWithdrawLog extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reward_withdraw_log', function (Blueprint $table) {
            $table->string('block')->nullable()->change();
            $table->string('amount')->nullable()->change();
            $table->datetime('withdraw_at')->nullable()->change();
            $table->datetime('updated_at')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reward_withdraw_log', function (Blueprint $table) {
            $table->string('block')->change();
            $table->string('amount')->change();
            $table->timestamp('withdraw_at')->change();
            $table->dropColumn('updated_at');
        });
    }
}
