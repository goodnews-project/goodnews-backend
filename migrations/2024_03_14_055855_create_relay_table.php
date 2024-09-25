<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateRelayTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('relay', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('inbox_url');
            $table->string('follow_activity_id')->nullable();
            $table->tinyInteger('state')->default(0)->comment('one of [0:idle, 1:pending, 2:accepted, 3:rejected]');
            $table->datetimes();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('relay');
    }
}
