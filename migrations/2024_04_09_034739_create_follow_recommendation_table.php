<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateFollowRecommendationTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('follow_recommendation', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->comment('account.id');
            $table->string('language', 24)->nullable()->comment('account.language');
            $table->decimal('rank')->nullable()->comment('排名、权重');
            $table->tinyInteger('status')->default(1)->comment('1 推荐 2 禁用推荐');
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follow_recommendation');
    }
}
