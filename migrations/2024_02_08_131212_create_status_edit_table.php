<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateStatusEditTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('status_edit', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('status_id')->unsigned()->index();
            $table->bigInteger('account_id')->unsigned()->index();
            $table->text('content')->nullable();
            $table->text('spoiler_text')->nullable();
            $table->json('ordered_attachment_ids')->nullable();
            $table->json('attachment_descriptions')->nullable();
            $table->json('poll_options')->nullable();
            $table->boolean('is_sensitive')->nullable();
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_edit');
    }
}
