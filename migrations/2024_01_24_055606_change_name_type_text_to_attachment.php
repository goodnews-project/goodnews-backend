<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class ChangeNameTypeTextToAttachment extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attachment', function (Blueprint $table) {
            $table->text('name')->nullable()->comment('media name')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attachment', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
}
