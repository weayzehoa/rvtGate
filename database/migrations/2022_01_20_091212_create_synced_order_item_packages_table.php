<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSyncedOrderItemPackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_SYNCED_ORDER_ITEM_PACKAGES')) {
            Schema::create('synced_order_item_packages', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('order_id')->comment('訂單id');
                $table->unsignedInteger('order_item_id')->comment('訂單商品id');
                $table->unsignedInteger('order_item_package_id')->comment('訂單組合商品單品id');
                $table->string('erp_order_no',11)->comment('erp訂單編號');
                $table->longText('erp_order_sno')->comment('erp訂單序號');
                $table->bigInteger('purchase_no')->nullable()->comment('採購單單號');
                $table->date('purchase_date', 10)->nullable()->comment('採購單建立日期');
                $table->string('erp_purchase_no',11)->nullable()->comment('erp採購單編號');
                $table->date('book_shipping_date', 10)->nullable()->comment('預定出貨日');
                $table->date('vendor_arrival_date', 10)->nullable()->comment('廠商到貨日');
                $table->unsignedInteger('product_model_id')->comment('產品model_id');
                $table->string('unit_name')->nullable()->comment('單位');
                $table->integer('price')->nullable()->default(0)->comment('單價');
                $table->decimal('purchase_price',10,2)->nullable()->default(0)->comment('採購單價');
                $table->integer('gross_weight')->nullable()->default(0)->comment('毛重');
                $table->integer('net_weight')->nullable()->default(0)->comment('淨重');
                $table->integer('quantity')->nullable()->default(0)->comment('數量');
                $table->longText('admin_memo')->nullable()->comment('備註');
                $table->boolean('direct_shipment')->nullable()->comment('是否直寄，0:否，1:是');
                $table->boolean('is_del')->nullable()->default(0)->comment('是否刪除，0:否，1:是');
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
        if (env('DB_MIGRATE_SYNCED_ORDER_ITEM_PACKAGES')) {
            Schema::dropIfExists('synced_order_item_packages');
        }
    }
}
