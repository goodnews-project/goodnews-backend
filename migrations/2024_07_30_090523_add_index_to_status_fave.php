<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class AddIndexToStatusFave extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('status_fave', function (Blueprint $table) {
            $table->index('target_account_id', 'idx_target_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('status_fave', function (Blueprint $table) {
            $table->dropIndex('idx_target_account_id');
        });
    }
}
