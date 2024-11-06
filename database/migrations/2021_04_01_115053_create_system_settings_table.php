<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSystemSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_SYSTEM_SETTINGS')) {
            Schema::create('system_settings', function (Blueprint $table) {
                $table->id();
                $table->float('exchange_rate_RMB', 4, 2)->default(0)->comment('人民幣匯率');
                $table->float('exchange_rate_SGD', 4, 2)->default(0)->comment('新加坡幣匯率');
                $table->float('exchange_rate_MYR', 4, 2)->default(0)->comment('馬來西亞幣匯率');
                $table->float('exchange_rate_HKD', 4, 2)->default(0)->comment('港幣匯率');
                $table->float('exchange_rate_USD', 4, 2)->default(0)->comment('美金匯率');
                $table->string('sms_supplier')->comment('SMS供應商');
                $table->string('email_supplier')->comment('Email供應商');
                $table->string('customer_service_supplier')->comment('客服系統供應商');
                $table->string('payment_supplier')->comment('金流供應商');
                $table->string('invoice_supplier')->comment('發票供應商');
                $table->string('disable_ip_start')->nullable()->comment('關閉IP檢查開始時間');
                $table->string('disable_ip_end')->nullable()->comment('關閉IP檢查結束時間');
                $table->float('gross_weight_rate', 4, 2)->default(0)->comment('毛重倍率');
                $table->unsignedInteger('admin_id')->comment('管理員id');
                $table->unsignedInteger('twpay_quota')->comment('TWPAY可折抵總額');
                $table->unsignedInteger('mitake_points')->default(0)->comment('三竹簡訊餘額');
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
        if (env('DB_MIGRATE_SYSTEM_SETTINGS')) {
            Schema::dropIfExists('system_settings');
        }
    }
}
