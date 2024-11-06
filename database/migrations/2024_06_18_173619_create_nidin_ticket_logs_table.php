<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNidinTicketLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_NIDIN_TICKET_LOGS_TABLE')) {
            Schema::create('nidin_ticket_logs', function (Blueprint $table) {
                $table->id();
                $table->string('type')->nullable()->comment('處理類型');
                $table->string('nidin_order_no')->nullable()->comment('商家訂單編號');
                $table->string('transaction_id')->nullable()->comment('金流交易序號');
                $table->string('platform_no')->nullable()->comment('卷商編號');
                $table->string('key')->nullable()->comment('卷商key');
                $table->longText('from_nidin')->nullable();
                $table->longText('to_acpay')->nullable();
                $table->longText('from_acpay')->nullable();
                $table->longText('to_nidin')->nullable();
                $table->integer('rtnCode')->nullable()->comment('返回代碼');
                $table->string('rtnMsg')->nullable()->comment('返回訊息');
                $table->string('message')->nullable()->comment('message');
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
        if (env('DB_MIGRATE_NIDIN_TICKET_LOGS_TABLE')) {
            Schema::dropIfExists('nidin_ticket_logs');
        }
    }
}
