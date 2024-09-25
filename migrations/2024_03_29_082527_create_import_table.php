<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateImportTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('import', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->comment('account.id');
            $table->tinyInteger('type')->nullable()->comment('导入类型：1关注列表 2书签 3列表 4隐藏列表 5屏蔽列表 6域名屏蔽列表');
            $table->tinyInteger('status')->nullable()->comment('状态 1导入中 2已完成');
            $table->tinyInteger('mode')->nullable()->comment('模式 1合并 2覆盖');
            $table->integer('imported_count')->default(0)->comment('已导入数量');
            $table->integer('import_total')->default(0)->comment('导入总数');
            $table->integer('fail_count')->nullable()->comment('失败数量');
            $table->integer('fail_file')->nullable()->comment('失败文件');
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import');
    }
}
