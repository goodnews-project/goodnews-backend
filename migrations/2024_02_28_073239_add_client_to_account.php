<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class AddClientToAccount extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('account', function (Blueprint $table) {
            $table->tinyInteger('client')->nullable()->comment('1: 网页端 2：app');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account', function (Blueprint $table) {
            $table->dropColumn('client');
        });
    }
}
