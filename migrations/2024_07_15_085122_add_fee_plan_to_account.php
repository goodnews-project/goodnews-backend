<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class AddFeePlanToAccount extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('account', function (Blueprint $table) {
            $table->string('fee')->nullable()->comment('订阅价格/大于0即是允许付费订阅');
            $table->json('subscriber_plan')->nullable()->comment('长期订阅计划');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account', function (Blueprint $table) {
            $table->dropColumn('fee');
            $table->dropColumn('subscriber_plan');
        });
    }
}
