<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class AddDeletedAccountToConversation extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('conversation', function (Blueprint $table) {
            $table->json('deleted_account')->nullable()->comment('已删除的账号ID');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversation', function (Blueprint $table) {
            $table->dropColumn('deleted_account');
        });
    }
}
