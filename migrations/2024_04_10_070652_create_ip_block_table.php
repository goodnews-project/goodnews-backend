<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateIpBlockTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ip_block', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->ipAddress('ip')->comment('ip地址')->unique('uniq_ip');
            $table->tinyInteger('severity')->comment('规则限制程度 1 限制注册 2 阻止注册 3 阻止访问');
            $table->dateTime('expires_at')->comment('失效时间');
            $table->unsignedInteger('expires_in')->comment('多少秒后失效');
            $table->text('comment')->nullable()->comment('备注');
            $table->datetimes();
            $table->comment('ip规则表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_block');
    }
}
