<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateUserTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_id')->index()->comment('Who is the target of this user');
            $table->string('email', 64)->unique()->comment('confirmed email address for this user, this should be unique -- only one email address registered per instance, multiple users per email are not supported')->unique();
            $table->string('encrypted_password')->comment('confirmed email address for this user, this should be unique -- only one email address registered per instance, multiple users per email are not supported');
            $table->string('profile_image')->nullable();
            $table->ipAddress('signup_ip')->nullable()->comment('From what IP was this user created');
            $table->dateTime('current_signin_at')->nullable()->comment('When did the user sign in with their current session');
            $table->ipAddress('current_signin_ip')->nullable()->comment('What/s the previous IP of this user');
            $table->unsignedInteger('signin_count')->default('0')->comment('How many times has this user signed in');
            $table->string('locale', 24)->nullable()->comment('In what timezone/locale is this user located');
            $table->dateTime('last_emailed_at')->nullable()->comment('When was this user last contacted by email');
            $table->string('confirmation_token', 64)->nullable()->comment('What confirmation token did we send this user/what are we expecting back');
            $table->dateTime('confirmation_sent_at')->nullable()->comment('When did we send email confirmation to this user');
            $table->dateTime('confirmed_at')->nullable()->comment('When did the user confirm their email address');
            $table->boolean('is_moderator')->default(false)->comment('Is this user a moderator');
            $table->boolean('is_admin')->default(false)->comment('Is this user an admin');
            $table->boolean('is_disable')->default(false)->comment('Is this user disabled from posting');
            $table->boolean('is_approve')->default(false)->comment('Has this user been approved by a moderator');
            $table->string('reset_password_token', 64)->nullable()->comment('The generated token that the user can use to reset their password');
            $table->dateTime('reset_password_sent_at')->nullable()->comment('When did we email the user their reset-password email');
            $table->unsignedBigInteger('role_id')->nullable()->comment('角色 ID');
            $table->datetimes();
            $table->comment('user table, User represents an actual human user of social. Note, this is a LOCAL social user, not a remote account');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user');
    }
}
