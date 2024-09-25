<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateConversationTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('conversation', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('c_id')->unique()->comment('conversation unique id');
            $table->unsignedBigInteger('to_id')->index();
            $table->unsignedBigInteger('from_id')->index();
            $table->unsignedBigInteger('dm_id')->nullable();
            $table->unsignedBigInteger('status_id')->nullable();
            $table->tinyInteger('dm_type')->nullable();
            $table->datetimes();
            $table->comment('会话表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation');
    }
}
