<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreatePollVoteTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('poll_vote', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('status_id')->index()->comment('status.id');
            $table->unsignedBigInteger('account_id')->comment('account.id');
            $table->unsignedBigInteger('poll_id')->index()->comment('poll.id');
            $table->unsignedInteger('choice')->default(0)->comment('投票项');
            $table->unique(['account_id', 'poll_id', 'choice']);
            $table->datetimes();
            $table->comment('投票表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poll_vote');
    }
}
