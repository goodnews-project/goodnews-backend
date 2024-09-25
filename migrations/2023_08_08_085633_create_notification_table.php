<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateNotificationTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notification', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('status_id')->nullable()->comment('statuses.id, If the notification pertains to a status, what is the database ID of that status');
            $table->unsignedBigInteger('account_id')->comment('account.id, ID of the account that performed the action that created the notification');
            $table->unsignedInteger('target_account_id')->comment('account.id, ID of the account targeted by the notification (ie., who will receive the notification?)');
            $table->tinyInteger('notify_type')->nullable()->comment('Type of this notification; 1:follow 2:mention 3:reblog 4:favourite 5:status');
            $table->boolean('read')->default(false)->comment('Notification has been seen/read');
            $table->datetimes();
            $table->comment('notification table, Notification models an alert/notification sent to an account about something like a reblog, like, new follow request, etc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification');
    }
}
