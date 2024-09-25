<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateAccountWarningTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('account_warning', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->index();
            $table->unsignedBigInteger('target_account_id')->index();
            $table->unsignedTinyInteger('action');
            $table->text('text')->nullable();
            $table->unsignedBigInteger('report_id')->index();
            $table->dateTime('overruled_at')->nullable();
            $table->datetimes();
        });

        Schema::table('account',function (Blueprint $table){
           $table->datetime('silenced_at')->after('suspended_at')->nullable()->comment('禁言时间');  
        //    $table->datetime('suspended_at')->nullable()->comment('暂停时间'); 
           $table->datetime('sensitized_at')->after('suspended_at')->nullable()->comment('敏感用户时间'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_warning');


    }
}
