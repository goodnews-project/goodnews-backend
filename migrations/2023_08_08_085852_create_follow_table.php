<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateFollowTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('follow', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->index()->comment('Who does this follow originate from');
            $table->unsignedInteger('target_account_id')->index()->comment('Who is the target of this follow');
            $table->boolean('show_reb_logs')->default(true)->comment('Does this follow also want to see reblogs and not just posts');
            $table->boolean('notify')->default(false)->comment('does the following account want to be notified when the followed account posts');
            $table->datetimes();
            $table->comment('follow table, Follow represents one account following another, and the metadata around that follow');

            $table->unique(['account_id','target_account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follow');
    }
}
