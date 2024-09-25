<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class AddViewsToStatusTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('status', function (Blueprint $table) {
            $table->unsignedBigInteger('view_count')->default(0)->after('reblog_count')->index();
            $table->timestamp('view_count_updated_at')->nullable()->after('reblog_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('status', function (Blueprint $table) {
            $table->dropColumn(['view_count','view_count_updated_at']);
        });
    }
}
