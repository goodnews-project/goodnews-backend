<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreatePollTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('poll', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('status_id')->index()->comment('status.id');
            $table->unsignedBigInteger('account_id')->index()->comment('account.id');
            $table->json('poll_options')->nullable()->comment('投票的选项，是一个字符串数组。');
            $table->json('cached_tallies')->nullable()->comment('缓存的统计信息，包含各个选项的投票计数的数组。');
            $table->boolean('multiple')->default(false)->comment('用户是否可以选择多个选项进行投票');
            $table->boolean('hide_totals')->default(false)->comment('是否隐藏投票总数');
            $table->unsignedInteger('votes_count')->default(0)->comment('投票总数');
            $table->unsignedInteger('voters_count')->default(0)->comment('参与投票的用户数');
            $table->dateTime('last_fetched_at')->nullable()->comment('最后一次获取投票信息的时间。');
            $table->dateTime('expires_at')->nullable()->comment('投票的过期时间');
            $table->datetimes();
            $table->comment('投票选项表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poll');
    }
}
