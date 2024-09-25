<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateHashtagTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hashtag', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique()->index()->comment('tag name');
            $table->string('slug')->unique()->index()->comment('friendly name');
            $table->string('href')->nullable()->comment('tag href');
            $table->boolean('is_sensitive')->default(false)->comment('是否敏感');
            $table->boolean('is_banned')->default(false)->comment('是否被禁用');
            $table->datetimes();
            $table->comment('话题标签表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hashtag');
    }
}
