<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateRewardWithdrawLogTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reward_withdraw_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('address')->index();
            $table->string('account_id')->index()->nullable()->index();

            $table->string('hash')->unique();
            $table->string('block')->index();

            $table->unsignedBigInteger('amount');

            $table->timestamp('withdraw_at');
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_withdraw_log');
    }
}
