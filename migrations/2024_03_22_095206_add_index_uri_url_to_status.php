<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class AddIndexUriUrlToStatus extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('status', function (Blueprint $table) {
            $table->string('uri')->nullable()->comment('activitypub URI of this status')->index('idx_uri')->change();
            $table->string('url')->nullable()->comment('web url for viewing this status')->index('idx_url')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('status', function (Blueprint $table) {
            $table->dropIndex('idx_uri');
            $table->dropIndex('idx_url');
        });
    }
}
