<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class DropWalletAddressIndexToAccount extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('account', function (Blueprint $table) {
            $table->dropIndex('account_wallet_address_unique');
            $table->index('wallet_address', 'idx_wallet_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account', function (Blueprint $table) {
            $table->unique('wallet_address', 'account_wallet_address_unique');
            $table->dropIndex('idx_wallet_address');
        });
    }
}
