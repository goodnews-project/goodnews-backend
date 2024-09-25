<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateBookmarkTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookmark', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('status_id')->comment('status.id');
            $table->unsignedBigInteger('account_id')->index()->comment('account.id');
            $table->unique(['status_id', 'account_id']);
            $table->datetimes();
            $table->comment('书签表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookmark');
    }
}
