<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class AddExpiresInToFilterTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('filter', function (Blueprint $table) {
            $table->unsignedInteger('expires_in')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('filter', function (Blueprint $table) {
            $table->dropColumn('expires_in');
        });
    }
}
