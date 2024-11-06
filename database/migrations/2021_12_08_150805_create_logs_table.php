<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_LOGS')) {
            Schema::create('logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('admin_id')->nullable()->comment('管理者id');
                $table->string('db_name')->nullable()->comment('鼎新資料庫代號');
                $table->string('type')->nullable()->comment('類型, 新增、刪除、修改');
                $table->longText('sku')->nullable()->comment('sku');
                $table->longText('digiwin_no')->nullable()->comment('digiwin_no');
                $table->longText('old_data')->nullable()->comment('舊資料');
                $table->longText('data')->nullable()->comment('儲存資料');
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
        if (env('DB_MIGRATE_LOGS')) {
            Schema::dropIfExists('logs');
        }
    }
}
