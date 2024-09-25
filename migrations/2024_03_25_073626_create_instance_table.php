<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateInstanceTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('instance', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('domain')->unique()->index();
            $table->boolean('is_disable_download')->default(false);
            $table->boolean('is_proxy')->default(false);
            $table->boolean('is_disable_sync')->default(false);
            $table->boolean('is_banned')->default(false);
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instance');
    }
}
