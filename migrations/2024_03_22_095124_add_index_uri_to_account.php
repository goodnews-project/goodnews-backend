<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class AddIndexUriToAccount extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('account', function (Blueprint $table) {
            $table->string('uri')->nullable()->comment('ActivityPub URI for this account.')->index('idx_uri')->change();
            $table->string('public_key_uri')->nullable()->comment('Web-reachable location of this account/s public key')->index('idx_public_key_uri')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account', function (Blueprint $table) {
            $table->dropIndex('idx_uri');
            $table->dropIndex('idx_public_key_uri');
        });
    }
}
