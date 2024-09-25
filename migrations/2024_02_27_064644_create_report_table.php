<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateReportTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('report', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->nullable()->comment('account.id');
            $table->unsignedBigInteger('target_account_id')->nullable()->comment('account.id,ID of the account to report');
            $table->json('status_ids')->nullable()->comment('status.id,You can attach statuses to the report to provide additional context');
            $table->string('comment')->nullable()->comment('The reason for the report. Default maximum of 1000 characters');
            $table->boolean('forward')->default(false)->comment('If the account is remote, should the report be forwarded to the remote admin? Defaults to false');
            $table->string('category', 24)->default('other')->comment('Specify if the report is due to spam, violation of enumerated instance rules, or some other reason');
            $table->json('rule_ids')->nullable()->comment('instance_rule.id,For violation category reports, specify the ID of the exact rules broken');
            $table->json('forward_to_domains')->nullable();
            $table->unsignedBigInteger('assigned_account_id')->nullable();
            $table->unsignedBigInteger('action_taken_by_account_id')->nullable();
            $table->dateTime('action_taken_at')->nullable();
            $table->json('meta')->nullable();
            $table->string('uri')->nullable();
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report');
    }
}
