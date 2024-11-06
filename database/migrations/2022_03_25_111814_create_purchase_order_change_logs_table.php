<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseOrderChangeLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_PURCHASE_ORDER_CHANGE_LOGS')) {
            Schema::create('purchase_order_change_logs', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('purchase_no')->comment('採購單單號');
                $table->unsignedInteger('admin_id')->nullable()->comment('admin_id');
                $table->unsignedInteger('poi_id')->nullable()->comment('purchase_order_item_id');
                $table->unsignedInteger('poip_id')->nullable()->comment('purchase_order_item_package_id');
                $table->string('sku')->nullable()->comment('sku號碼');
                $table->string('digiwin_no')->nullable()->comment('鼎新品號');
                $table->string('product_name')->nullable()->comment('商品名稱');
                $table->string('quantity')->nullable()->comment('數量');
                $table->string('price')->nullable()->comment('金額');
                $table->string('date')->nullable()->comment('日期');
                $table->string('status')->nullable()->comment('狀態');
                $table->string('memo')->nullable()->comment('備註');
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
        if (env('DB_MIGRATE_PURCHASE_ORDER_CHANGE_LOGS')) {
            Schema::dropIfExists('purchase_order_change_logs');
        }
    }
}
