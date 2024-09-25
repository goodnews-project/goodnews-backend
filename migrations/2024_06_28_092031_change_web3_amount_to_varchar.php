<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class ChangeWeb3AmountToVarchar extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reward_log', function (Blueprint $table) {
            $table->string("amount")->change();
        });
        Schema::table('reward_withdraw_log', function (Blueprint $table) {
            $table->string("amount")->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reward_log', function (Blueprint $table) {
            $table->unsignedBigInteger("amount")->change();
        });
        Schema::table('reward_withdraw_log', function (Blueprint $table) {
            $table->unsignedBigInteger("amount")->change();
        });
    }
}
