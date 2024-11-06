<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmsLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_SMS_LOGS')) {
            Schema::create('sms_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('sms_id')->comment('SMS時間標記'); //舊程式欄位 $sms_id = time()
                $table->integer('admin_id')->nullable()->comment('對應管理者id');
                $table->integer('user_id')->nullable()->comment('對應使用者id');
                $table->string('vendor', 10)->nullable()->comment('簡訊供應商');
                $table->longText('send_response')->nullable();
                $table->longText('get_response')->nullable();
                $table->string('status', 20)->nullable()->comment('狀態');
                $table->text('message')->nullable()->comment('訊息');
                $table->string('msg_id')->nullable();
                $table->string('aws_id')->nullable();
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
        if (env('DB_MIGRATE_SMS_LOGS')) {
            Schema::dropIfExists('sms_logs');
        }
    }
}
