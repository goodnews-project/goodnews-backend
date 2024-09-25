<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class AddColumnSpoilerTextToStatus extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('status', function (Blueprint $table) {
            $table->text('spoiler_text')->nullable()->comment('');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('status', function (Blueprint $table) {
            $table->dropColumn('spoiler_text');
        });
    }
}
