<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateMuteTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mute', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('target_account_id');
            $table->timestamp('expires_at')->nullable()->comment('过期时间');
            $table->datetimes();
            $table->index('target_account_id');
            $table->index(['target_account_id','account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mute');
    }
}
