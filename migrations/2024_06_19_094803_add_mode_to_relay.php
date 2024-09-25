<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class AddModeToRelay extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('relay', function (Blueprint $table) {
            $table->boolean('mode')->nullable()->comment('同步模式：1 只读 2 只写');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('relay', function (Blueprint $table) {
            $table->dropColumn('mode');
        });
    }
}
