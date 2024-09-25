<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class AddUnreadNumToAccountSubscriberLog extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('account_subscriber_log', function (Blueprint $table) {
            $table->unsignedInteger('unread_num')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_subscriber_log', function (Blueprint $table) {
            $table->dropColumn('unread_num');
        });
    }
}
