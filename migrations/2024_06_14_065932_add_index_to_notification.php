<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class AddIndexToNotification extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notification', function (Blueprint $table) {
            $table->index('account_id');
            $table->index('target_account_id');
            $table->index('status_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification', function (Blueprint $table) {
            $table->dropIndex('account_id');
            $table->dropIndex('target_account_id');
            $table->dropIndex('status_id'); 
        });
    }
}
