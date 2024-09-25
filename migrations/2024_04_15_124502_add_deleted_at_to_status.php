<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class AddDeletedAtToStatus extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('status', function (Blueprint $table) {
            $table->dateTime('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('status', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });
    }
}
