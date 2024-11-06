<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVendorShippingItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_VENDOR_SHIPPING_ITEMS_TABLE')) {
            Schema::create('vendor_shipping_items', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('shipping_no')->index()->comment('出貨單號');
                $table->bigInteger('purchase_no')->comment('採購單單號');
                $table->unsignedInteger('product_model_id')->comment('商品 model id');
                $table->integer('poi_id')->nullable()->comment('purchase_order_item id');
                $table->integer('ori_id')->nullable()->comment('order_item id');
                $table->integer('si_id')->nullable()->comment('sell import id');
                $table->string('product_name')->comment('商品名稱');
                $table->string('sku')->comment('EC貨號');
                $table->string('digiwin_no')->comment('鼎新貨號');
                $table->string('gtin13')->nullable()->comment('當下的條碼');
                $table->string('order_ids')->comment('訂單ids');
                $table->string('order_numbers')->comment('訂單號碼s');
                $table->integer('quantity')->comment('數量');
                $table->boolean('direct_shipment')->comment('是否直寄，0:否，1:是');
                $table->date('vendor_arrival_date')->comment('廠商預定到貨日');
                $table->boolean('is_del')->nullable()->default(0)->comment('是否取消，0:否，1:是');
                $table->date('shipping_date')->nullable()->comment('出貨日期');
                $table->string('express_way')->nullable()->comment('物流商');
                $table->string('express_no')->nullable()->comment('物流單號');
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
        if (env('DB_MIGRATE_VENDOR_SHIPPING_ITEMS_TABLE')) {
            Schema::dropIfExists('vendor_shipping_items');
        }
    }
}
