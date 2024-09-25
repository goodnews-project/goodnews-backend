<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateStatusTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('status', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->comment('account.id, which account posted this status');
            $table->unsignedBigInteger('reply_to_id')->nullable()->comment('statuses.id, id of the status this status replies to');
            $table->unsignedBigInteger('reply_to_account_id')->nullable()->comment('account.id, id of the account that this status replies to');
            $table->unsignedBigInteger('reblog_id')->nullable()->comment('statuses.id, id of the statuses that this status reblog to');
            $table->string('uri')->nullable()->comment('activitypub URI of this status');
            $table->string('url')->nullable()->comment('web url for viewing this status');
            $table->text('content')->nullable()->comment('content of this status; likely html-formatted but not guaranteed');
            $table->boolean('is_local')->default(false)->comment('is this status from a local account');
            $table->boolean('is_sensitive')->default(false)->comment('mark the status as sensitive');
            $table->boolean('comments_disabled')->default(false);
            $table->boolean('who_can_reply')->default(false)->comment('谁能回复推文；0 所有人 1：account you follow 2:only accounts you mention');
            $table->boolean('is_hidden_reply')->default(false)->comment('隐藏评论');
            $table->tinyInteger('scope')->default(1)->comment('visibility scope 1:public 2:private 3:direct');
            $table->dateTime('pinned_at')->nullable()->comment('Status was pinned by owning account at this time.');
            $table->unsignedBigInteger('fave_count')->default(0)->comment('fave count of this status');
            $table->unsignedBigInteger('reply_count')->default(0)->comment('reply count of this status');
            $table->unsignedBigInteger('reblog_count')->default(0)->comment('reblog count of this status');
            $table->datetimes();
            $table->comment('statuses table, Status represents a user-created "post" or "status" in the database, either remote or local');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status');
    }
}
