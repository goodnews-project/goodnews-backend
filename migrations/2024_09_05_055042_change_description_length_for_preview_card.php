<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class ChangeDescriptionLengthForPreviewCard extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('preview_card', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('preview_card', function (Blueprint $table) {
            $table->string('description')->nullable()->change();
        });
    }
}
