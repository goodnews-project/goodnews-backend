<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateStatusFaveTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('status_fave', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('status_id')->index()->comment('statuses.id, id of the status that has been faved');
            $table->unsignedBigInteger('account_id')->index()->comment('account.id, id of the account that created ("did") the fave');
            $table->unsignedBigInteger('target_account_id')->comment('account.id, id the account owning the faved status');
            $table->string('uri')->nullable()->comment('ActivityPub URI of this fave');
            $table->datetimes();
            $table->comment('statuses_fave table, StatusFave refers to a "fave" or "like" in the database, from one account, targeting the status of another account');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_fave');
    }
}
