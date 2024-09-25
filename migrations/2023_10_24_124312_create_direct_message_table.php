<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateDirectMessageTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('direct_message', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('to_id')->index()->comment('account.id, recipient');
            $table->unsignedBigInteger('from_id')->index()->comment('account.id, sender');
            $table->unsignedBigInteger('status_id')->index()->comment('status.id');
            $table->tinyInteger('dm_type')->default('1')->nullable()->index()->comment('message type, 1.text 2.photo 3.video');
            $table->unique(['to_id', 'from_id', 'status_id']);
            $table->dateTime('read_at')->nullable()->comment('查看时间');
            $table->datetimes();
            $table->comment('私信表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('direct_message');
    }
}
