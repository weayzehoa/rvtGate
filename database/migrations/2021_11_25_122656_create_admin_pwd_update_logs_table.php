<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdminPwdUpdateLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_ADMIN_PWD_UPDATE_LOGS')) {
            Schema::create('admin_pwd_update_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('admin_id')->nullable()->comment('管理者id');
                $table->string('password')->nullable()->comment('密碼');
                $table->string('ip',20)->nullable()->comment('來源IP');
                $table->unsignedInteger('editor_id')->nullable()->comment('修改者id');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (env('DB_MIGRATE_ADMIN_PWD_UPDATE_LOGS')) {
            Schema::dropIfExists('admin_pwd_update_logs');
        }
    }
}
