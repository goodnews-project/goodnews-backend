<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class PreviewCardStatus extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('preview_cards_status', function (Blueprint $table) {
            $table->unsignedBigInteger('preview_card_id');
            $table->unsignedBigInteger('status_id');

            $table->primary(['preview_card_id','status_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preview_cards_status');
    }
}
