<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateRolePermissionTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('role_permission', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('role_id')->nullable()->comment('role.id');
            $table->unsignedInteger('permission_id')->nullable()->comment('permissions.id');
            $table->unique(['role_id', 'permission_id']);
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_permission');
    }
}
