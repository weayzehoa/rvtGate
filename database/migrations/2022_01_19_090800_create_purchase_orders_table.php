<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_PURCHASE_ORDERS')) {
            Schema::create('purchase_orders', function (Blueprint $table) {
                $table->id();
                $table->string('type', 4)->comment('erp採購單單別');
                $table->bigInteger('purchase_no')->comment('採購單id');
                $table->string('erp_purchase_no', 11)->nullable()->comment('erp採購單號碼');
                $table->unsignedInteger('vendor_id')->comment('商家id');
                $table->integer('quantity')->nullable()->comment('總數量');
                $table->decimal('amount',10,2)->nullable()->comment('總金額');
                $table->decimal('tax',10,2)->nullable()->comment('稅金');
                $table->boolean('tax_type')->nullable()->comment('稅別');
                $table->longText('order_ids')->nullable()->comment('iCarry訂單號碼');
                $table->longText('order_item_ids')->nullable()->comment('iCarry訂單商品號碼');
                $table->longText('product_model_ids')->nullable()->comment('商品 model id');
                $table->boolean('is_del')->nullable()->default(0)->comment('是否取消，0:否，1:是');
                $table->boolean('is_lock')->nullable()->default(0)->comment('是否鎖定，0:否，1:是');
                $table->boolean('status')->nullable()->default(0)->comment('狀態');
                $table->boolean('arrival_date_changed')->nullable()->default(0)->comment('廠商到貨日變動判斷');
                $table->string('memo')->nullable()->comment('備註');
                $table->dateTime('synced_time')->nullable()->comment('同步時間');
                $table->date('purchase_date')->nullable()->comment('採購日期');
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
        if (env('DB_MIGRATE_PURCHASE_ORDERS')) {
            Schema::dropIfExists('purchase_orders');
        }
    }
}
