<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_PURCHASE_ORDER_ITEMS')) {
            Schema::create('purchase_order_items', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('purchase_no')->comment('採購單單號');
                $table->unsignedInteger('product_model_id')->comment('商品 model id');
                $table->string('gtin13')->nullable()->comment('當下的條碼');
                $table->decimal('purchase_price',10,2)->comment('採購金額');
                $table->integer('quantity')->comment('數量');
                $table->date('vendor_arrival_date')->nullable()->comment('廠商預定到貨日');
                $table->string('memo')->nullable()->comment('備註');
                $table->boolean('direct_shipment')->nullable()->comment('是否直寄，0:否，1:是');
                $table->boolean('is_del')->nullable()->default(0)->comment('是否取消，0:否，1:是');
                $table->boolean('is_close')->nullable()->default(0)->comment('是否取消，0:否，1:是');
                $table->boolean('is_lock')->nullable()->default(0)->comment('是否鎖定，0:否，1:是');
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
        if (env('DB_MIGRATE_PURCHASE_ORDER_ITEMS')) {
            Schema::dropIfExists('purchase_order_items');
        }
    }
}
