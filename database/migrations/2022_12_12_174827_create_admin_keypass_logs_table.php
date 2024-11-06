<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdminKeypassLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_ADMIN_KEYPASS_LOGS')) {
            Schema::create('admin_keypass_logs', function (Blueprint $table) {
                $table->id();
                $table->string('type')->nullable()->comment('類別');
                $table->boolean('is_pass')->nullable()->default(0)->comment('是否成功，0:否，1:是');
                $table->string('memo')->nullable()->comment('紀錄');
                $table->integer('admin_id')->nullable()->comment('處理人');
                $table->string('admin_name')->nullable()->comment('處理人');
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
        if (env('DB_MIGRATE_ADMIN_KEYPASS_LOGS')) {
            Schema::dropIfExists('admin_keypass_logs');
        }
    }
}
