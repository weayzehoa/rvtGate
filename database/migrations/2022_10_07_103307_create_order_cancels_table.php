<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderCancelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_ORDER_CANCELS')) {
            Schema::create('order_cancels', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('order_id')->comment('訂單id');
                $table->bigInteger('order_number')->comment('訂單號碼');
                $table->string('order_digiwin_no')->comment('訂單商品鼎新品號');
                $table->string('purchase_digiwin_no')->nullable()->comment('採購單商品鼎新品號');
                $table->integer('quantity')->comment('取消數量');
                $table->date('book_shipping_date')->nullable()->comment('預定出貨日期');
                $table->date('vendor_arrival_date')->nullable()->comment('廠商到貨日期');
                $table->dateTime('cancel_time')->nullable()->comment('取消日期');
                $table->string('cancel_person')->nullable()->comment('取消人');
                $table->bigInteger('purchase_order_id')->nullable()->comment('採購單id');
                $table->bigInteger('purchase_no')->nullable()->comment('採購單號碼');
                $table->integer('deduct_quantity')->nullable()->comment('取消數量');
                $table->string('memo')->nullable()->comment('備註');
                $table->boolean('is_chk')->nullable()->default(0)->comment('是否處理，0:否，1:是');
                $table->boolean('direct_shipment')->nullable()->default(0)->comment('是否直寄，0:否，1:是');
                $table->dateTime('chk_date')->nullable()->comment('處理日期');
                $table->integer('admin_id')->nullable()->comment('處理人');
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
        if (env('DB_MIGRATE_ORDER_CANCELS')) {
            Schema::dropIfExists('order_cancels');
        }
    }
}
