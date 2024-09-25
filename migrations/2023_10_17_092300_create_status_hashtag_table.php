<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateStatusHashtagTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('status_hashtag', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('status_id')->index()->comment('status.id');
            $table->unsignedBigInteger('hashtag_id')->index()->comment('hashtag.id');
            $table->unsignedBigInteger('account_id')->comment('account.id');
            $table->unique(['status_id', 'hashtag_id']);
            $table->datetimes();
            $table->comment('status and hashtags relation table');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_hashtag');
    }
}
