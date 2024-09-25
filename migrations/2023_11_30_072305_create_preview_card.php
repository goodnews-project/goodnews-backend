<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreatePreviewCard extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('preview_card', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('url');
            $table->string('title')->nullable();
            $table->string('description')->nullable();
            $table->string('image_url')->nullable();

            $table->string('provider_name')->nullable()->comment('site name');
            $table->string('provider_url')->nullable()->comment('site url');
            
            $table->string('blurhash')->nullable()->comment('media blurhash');
            $table->integer('width')->nullable()->comment('media width');
            $table->integer('height')->nullable()->comment('media height');
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preview_card');
    }
}
