<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseOrderItemSinglesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_PURCHASE_ORDER_ITEM_SINGLES')) {
            Schema::create('purchase_order_item_singles', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('purchase_no')->comment('採購單單號');
                $table->unsignedInteger('poi_id')->nullable()->comment('purchase_order_item_id');
                $table->unsignedInteger('poip_id')->nullable()->comment('purchase_order_item_package_id');
                $table->string('erp_purchase_no', 16)->nullable()->comment('erp採購單號');
                $table->string('erp_purchase_sno', 16)->nullable()->comment('erp採購單序號');
                $table->date('stockin_date')->nullable()->comment('入庫日期');
                $table->unsignedInteger('product_model_id')->comment('商品 model id');
                $table->string('gtin13')->nullable()->comment('當下的條碼');
                $table->decimal('purchase_price', 10, 2)->comment('採購金額');
                $table->integer('quantity')->comment('數量');
                $table->date('vendor_arrival_date')->nullable()->comment('廠商預定到貨日');
                $table->boolean('direct_shipment')->nullable()->comment('是否直寄，0:否，1:是');
                $table->boolean('is_del')->nullable()->default(0)->comment('是否取消，0:否，1:是');
                $table->boolean('is_close')->nullable()->default(0)->comment('是否取消，0:否，1:是');
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
        if (env('DB_MIGRATE_PURCHASE_ORDER_ITEM_SINGLES')) {
            Schema::dropIfExists('purchase_order_item_singles');
        }
    }
}
