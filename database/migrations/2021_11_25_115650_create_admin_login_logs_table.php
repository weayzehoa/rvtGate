<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdminLoginLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_ADMIN_LOGIN_LOGS')) {
            Schema::create('admin_login_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('admin_id')->nullable()->comment('管理者id');
                $table->string('result')->nullable()->comment('登入結果');
                $table->string('account',50)->nullable()->comment('失敗帳號紀錄');
                $table->string('ip',20)->nullable()->comment('來源IP');
                $table->string('site',20)->nullable()->comment('來源site');
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
        if (env('DB_MIGRATE_ADMIN_LOGIN_LOGS')) {
            Schema::dropIfExists('admin_login_logs');
        }
    }
}
