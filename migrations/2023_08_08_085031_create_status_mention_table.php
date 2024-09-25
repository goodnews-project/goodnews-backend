<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateStatusMentionTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('status_mention', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('status_id')->index()->comment('statuses.id, ID of the status this mention originates from');
            $table->unsignedBigInteger('account_id')->comment('account.id, ID of the mention creator account');
            $table->unsignedBigInteger('target_account_id')->comment('account.id, Mention target/receiver account ID');
            $table->string('href')->nullable()->comment('mention href');
            $table->string('name')->nullable()->comment('mention name');
            $table->boolean('silent')->default(false)->comment('Prevent this mention from generating a notification');
            $table->datetimes();
            $table->comment('statuses_mention table, Mention refers to the "tagging" or "mention" of a user within a status.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_mention');
    }
}
