<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class AddIndexToStatusMention extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('status_mention', function (Blueprint $table) {
            $table->index('account_id', 'idx_account_id');
            $table->index('target_account_id', 'idx_target_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('status_mention', function (Blueprint $table) {
            $table->dropIndex('idx_account_id');
            $table->dropIndex('idx_target_account_id');
        });
    }
}
