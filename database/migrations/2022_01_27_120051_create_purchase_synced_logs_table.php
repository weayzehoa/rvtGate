<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseSyncedLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_PURCHASE_SYNCED_LOGS')) {
            Schema::create('purchase_synced_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('admin_id')->nullable()->comment('管理者id');
                $table->unsignedInteger('purchase_order_id')->comment('採購單id');
                $table->unsignedInteger('vendor_id')->comment('商家id');
                $table->integer('quantity')->comment('總數量');
                $table->decimal('amount',10,2)->comment('總金額');
                $table->decimal('tax',10,2)->comment('稅金');
                $table->boolean('status')->comment('狀態');
                $table->integer('export_no')->nullable()->comment('匯出單號');
                $table->dateTime('notice_time')->nullable()->comment('通知時間');
                $table->dateTime('confirm_time')->nullable()->comment('確認時間');
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
        if (env('DB_MIGRATE_PURCHASE_SYNCED_LOGS')) {
            Schema::dropIfExists('purchase_synced_logs');
        }
    }
}
