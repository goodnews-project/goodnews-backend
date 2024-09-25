<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreatePayLogTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pay_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('account_id')->unsigned()->index()->comment('支付用户,account.id');
            $table->bigInteger('target_account_id')->unsigned()->index()->comment('收款用户,account.id');
            $table->bigInteger('order_id')->unsigned()->index()->comment('web3支付内容ID，该值当type=1，为status.id; type=2,为订阅计划ID');
            $table->string('hash')->unique()->comment('交易hash');
            $table->string('fee')->nullable()->comment('交易金额');
            $table->string('send_addr')->comment('发送钱包地址');
            $table->string('recv_addr')->comment('接收钱包地址');
            $table->tinyInteger('state')->nullable()->comment('交易状态 1:success 2:fail');
            $table->tinyInteger('type')->nullable()->comment('交易类型 1:解锁内容 2:订阅用户');
            $table->string('block')->index();
            $table->dateTime('paid_at')->comment('链上交易时间');
            $table->datetimes();
            $table->comment('支付记录表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pay_log');
    }
}
