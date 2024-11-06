<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIcarryAcpayTicketLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_ICARRY_ACPAY_TICKET_LOGS_TABLE')) {
            Schema::connection('icarry')->create('acpay_ticket_logs', function (Blueprint $table) {
                $table->id();
                $table->string('type')->nullable()->comment('處理類型');
                $table->integer('user_id')->nullable()->comment('購買者id');
                $table->string('user_name')->nullable()->comment('購買者名字');
                $table->integer('admin_id')->nullable()->comment('管理者id');
                $table->string('admin_name')->nullable()->comment('管理者名字');
                $table->binary('post_json')->nullable();
                $table->binary('get_json')->nullable();
                $table->integer('rtnCode')->nullable()->comment('返回代碼');
                $table->string('rtnMsg')->nullable()->comment('返回訊息');
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
        if (env('DB_MIGRATE_ICARRY_ACPAY_TICKET_LOGS_TABLE')) {
            Schema::connection('icarry')->dropIfExists('acpay_ticket_logs');
        }
    }
}
