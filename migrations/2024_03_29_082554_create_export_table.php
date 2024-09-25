<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateExportTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('export', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->comment('account.id');
            $table->bigInteger('filesize')->nullable()->comment('文件大小');
            $table->string('file_url')->nullable()->comment('文件地址');
            $table->tinyInteger('status')->nullable()->comment('状态 1正在导出 2导出完成');
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export');
    }
}
