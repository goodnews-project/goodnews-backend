<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateUserFilterTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_filter', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->index()->comment('account.id');
            $table->unsignedBigInteger('filter_id')->index()->comment('filter.id');
            $table->unsignedBigInteger('status_id')->index()->comment('status.id');
            $table->unique(['account_id', 'filter_id', 'status_id'], 'uniq_filter');
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_filter');
    }
}
