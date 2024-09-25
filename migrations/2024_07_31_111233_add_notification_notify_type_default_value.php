<?php

use Doctrine\DBAL\Types\Type;
use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class AddNotificationNotifyTypeDefaultValue extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notification', function (Blueprint $table) {
            $table->unsignedInteger('notify_type')->unsigned()->default(0)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification', function (Blueprint $table) {
            $table->unsignedInteger('notify_type')->nullable()->change();
        });
    }
}
