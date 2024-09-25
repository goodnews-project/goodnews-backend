<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateStatusUnlockLogTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('status_unlock_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('account_id')->unsigned()->index()->comment('当前用户,account.id');
            $table->bigInteger('status_id')->unsigned()->index()->comment('解锁推文,status.id');
            $table->string('fee')->nullable()->comment('解锁支付的费用');
            $table->tinyInteger('state')->nullable()->comment('1:已解锁 2:未解锁');
            $table->integer('pay_log_id')->unsigned()->index()->comment('pay_log.id');
            $table->datetimes();
            $table->comment('推文内容解锁表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_unlock_log');
    }
}
