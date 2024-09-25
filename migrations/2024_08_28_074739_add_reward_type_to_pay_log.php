<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class AddRewardTypeToPayLog extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pay_log', function (Blueprint $table) {
            $table->tinyInteger('reward_type')->nullable()->comment('打赏类型');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pay_log', function (Blueprint $table) {
            $table->dropColumn('reward_type');
        });
    }
}
