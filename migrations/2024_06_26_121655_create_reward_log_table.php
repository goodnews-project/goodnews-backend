<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateRewardLogTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reward_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('from_address')->index();
            $table->string('from_account_id')->index()->nullable()->index();
            $table->string('to_address')->index();
            $table->string('to_account_id')->index()->nullable()->index();

            $table->string('hash')->unique();
            $table->string('block')->index();

            $table->unsignedBigInteger('amount');

            $table->timestamp('reward_at');
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_log');
    }
}
