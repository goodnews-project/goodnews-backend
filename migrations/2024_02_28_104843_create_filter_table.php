<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateFilterTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('filter', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title')->nullable()->comment('标题');
            $table->dateTime('expired_at')->nullable()->comment('失效时间');
            $table->json('context')->nullable()->comment('过滤环境 1：主页时间轴 2：通知 3：公共时间轴 4：对话 5：个人资料');
            $table->tinyInteger('act')->nullable()->comment('filter-action 1:隐藏时显示警告信息 2:完全隐藏');
            $table->json('kw_attr')->nullable()->comment('关键词');
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filter');
    }
}
