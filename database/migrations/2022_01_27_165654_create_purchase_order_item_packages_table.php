<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseOrderItemPackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_PURCHASE_ORDER_ITEM_PACKAGES')) {
            Schema::create('purchase_order_item_packages', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('purchase_no')->nullable()->comment('採購單號');
                $table->integer('purchase_order_item_id')->nullable()->comment('採購單商品id');
                $table->unsignedInteger('product_model_id')->comment('產品model_id');
                $table->string('gtin13')->nullable()->comment('當下的條碼');
                $table->decimal('purchase_price', 10, 2)->nullable()->default(0)->comment('採購單價');
                $table->integer('quantity')->nullable()->default(0)->comment('數量');
                $table->date('vendor_arrival_date', 10)->nullable()->comment('廠商到貨日');
                $table->string('memo')->nullable()->comment('備註');
                $table->boolean('direct_shipment')->nullable()->comment('是否直寄，0:否，1:是');
                $table->boolean('is_del')->nullable()->default(0)->comment('是否刪除，0:否，1:是');
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
        if (env('DB_MIGRATE_PURCHASE_ORDER_ITEM_PACKAGES')) {
            Schema::dropIfExists('purchase_order_item_packages');
        }
    }
}
