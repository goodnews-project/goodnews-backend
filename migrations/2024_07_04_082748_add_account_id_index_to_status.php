<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class AddAccountIdIndexToStatus extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('status', function (Blueprint $table) {
            $table->index('account_id');
            $table->index('reply_to_id');
            $table->index('reply_to_account_id');
            $table->index('reblog_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('status', function (Blueprint $table) {
            $table->dropColumn('account_id');
            $table->dropColumn('reply_to_id');
            $table->dropColumn('reply_to_account_id');
            $table->dropColumn('reblog_id');
        });
    }
}
