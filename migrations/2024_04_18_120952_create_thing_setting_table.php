<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateThingSettingTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('thing_setting', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('var', 24);
            $table->text('value')->nullable();
            $table->unsignedBigInteger('thing_id');
            $table->string('thing_type', 24);
            $table->unique(['thing_type', 'thing_id', 'var'], 'uniq_type_and_id_and_var');
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thing_setting');
    }
}
