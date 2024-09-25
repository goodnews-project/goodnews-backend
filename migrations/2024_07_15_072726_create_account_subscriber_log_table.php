<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateAccountSubscriberLogTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('account_subscriber_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('account_id')->unsigned()->index()->comment('当前用户,account.id');
            $table->bigInteger('target_account_id')->unsigned()->index()->comment('订阅目标,account.id');
            $table->integer('pay_log_id')->unsigned()->index()->comment('pay_log.id');
            $table->string('fee')->nullable()->comment('订阅支付的费用');
            $table->dateTime('expired_at')->nullable()->comment('订阅过期时间');
            $table->tinyInteger('state')->nullable()->comment('1: 订阅中 2:已取消 3:已过期');
            $table->integer('plan_id')->nullable()->comment('计划ID，account.subscriber_plan.id');
            $table->decimal('plan_discount')->nullable()->comment('折扣 0-1');
            $table->string('plan_fee')->nullable()->comment('计划费用');
            $table->integer('plan_term')->unsigned()->nullable()->comment('计划多少期（1/3/6/12月）');
            $table->datetimes();
            $table->comment('订阅付费记录表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_subscriber_log');
    }
}
