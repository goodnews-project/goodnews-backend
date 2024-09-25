<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateFollowRequestTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('follow_request', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedbigInteger('account_id')->unsigned()->index();
            $table->unsignedbigInteger('target_account_id')->unsigned()->index();
            $table->json('activity')->nullable();
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follow_request');
    }
}
