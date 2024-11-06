<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNidinPaymentLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_NIDIN_PAYMENT_LOGS_TABLE')) {
            Schema::create('nidin_payment_logs', function (Blueprint $table) {
                $table->id();
                $table->string('type')->nullable()->comment('類別');
                $table->longText('from_nidin')->nullable();
                $table->longText('to_nidin')->nullable();
                $table->longText('to_acpay')->nullable();
                $table->longText('from_acpay')->nullable();
                $table->string('nidin_order_no')->nullable()->comment('商家訂單編號');
                $table->string('transaction_id')->nullable()->comment('金流序號');
                $table->string('message')->nullable()->comment('訊息');
                $table->string('ip',20)->nullable()->comment('IP Address');
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
        if (env('DB_MIGRATE_NIDIN_PAYMENT_LOGS_TABLE')) {
            Schema::dropIfExists('nidin_payment_logs');
        }
    }
}
