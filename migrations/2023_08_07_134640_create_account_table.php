<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateAccountTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('account', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username', 64)->index()->comment('The username of the account, not including domain');
            $table->string('acct', 128)->unique()->comment('Equal to username for local users, or username@domain for remote users')->unique();
            $table->string('domain', 128)->index()->nullable()->comment('Domain of the account');
            $table->string('display_name', 128)->nullable()->comment('The display name of account');
            $table->dateTime('suspended_at')->nullable()->comment('When was this account suspended (eg., don/t allow it to log in/post, don/t accept media/posts from this account)');
            $table->boolean('is_sensitive')->default(false)->comment('Set posts from this account to sensitive by default');
            $table->text('note')->nullable()->comment('Bio/description of this account');
            $table->string('profile_image')->nullable();
            $table->string('profile_remote_image')->nullable();
            $table->string('avatar')->nullable()->comment('The avatar url of account');
            $table->string('avatar_remote_url')->nullable()->comment('For a non-local account, where can the header be fetched?');

            $table->string('uri')->nullable()->comment('ActivityPub URI for this account.');
            $table->string('url')->nullable()->comment('Web location of the account/s profile page');
            $table->string('inbox_uri')->nullable()->comment('Address of this account/s ActivityPub inbox, for sending activity to');
            $table->string('shared_inbox_uri')->nullable()->comment('Address of this account/s ActivityPub sharedInbox. Gotcha warning: this is a string pointer because it has three possible states: 1. We don/t know yet if the account has a shared inbox -- null. 2. We know it doesn/t have a shared inbox -- empty string. 3. We know it does have a shared inbox -- url string');
            $table->string('outbox_uri')->nullable()->comment('Address of this account/s activitypub outbox');
            $table->string('following_uri')->nullable()->comment('URI for getting the following list of this account');
            $table->string('followers_uri')->nullable()->comment('URI for getting the followers list of this account');
            $table->string('public_key_uri')->nullable()->comment('Web-reachable location of this account/s public key');
            $table->text('public_key')->nullable()->comment('Publickey for encoding activitypub requests, will be defined for both local and remote accounts');
            $table->text('private_key')->nullable()->comment('Privatekey for validating activitypub requests, will only be defined for local accounts');
            $table->string('language', 24)->nullable()->comment('What language does this account post in');
            $table->unsignedBigInteger('followers_count')->default('0')->comment('Number of accounts following this account');
            $table->unsignedBigInteger('following_count')->default('0')->comment('Number of accounts followed by this account');
            $table->unsignedBigInteger('status_count')->default(0);
            $table->tinyInteger('actor_type')->default('0')->comment('One of [1:Application 2:Group 3:Organization 4:Person 5:Service]');
            $table->boolean('is_activate')->default(false)->comment('是否激活');
            $table->dateTime('last_webfingered_at')->nullable()->comment('Last time this account was refreshed/located with webfinger');
            $table->datetimes();
            $table->comment('account table, Account represents either a local or a remote fediverse account, gotosocial or otherwise (mastodon, pleroma, etc)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account');
    }
}
