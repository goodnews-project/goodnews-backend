<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class MakeListTables extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('list', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->index();
            $table->string('title');
            $table->datetimes();
        });

        Schema::create('list_account', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('list_id')->index();
            $table->unsignedBigInteger('account_id');
            $table->datetimes();
        }); 
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('list');
        Schema::dropIfExists('list_account');
    }
}
