<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSyncedOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_SYNCED_ORDERS')) {
            Schema::create('synced_orders', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('order_id')->comment('icarry訂單id');
                $table->string('erp_order_no',11)->comment('erp訂單編號');
                $table->integer('admin_id',)->comment('管理者id');
                $table->integer('amount')->nullable()->default(0)->comment('總價');
                $table->integer('spend_point')->nullable()->default(0)->comment('使用購物金');
                $table->integer('shipping_fee')->nullable()->default(0)->comment('運費');
                $table->integer('parcel_tax')->nullable()->default(0)->comment('行郵稅(海外需計算)，這裡是指金額，不是%數');
                $table->integer('discount')->nullable()->default(0)->comment('折扣');
                $table->boolean('status')->default(0)->comment('0:訂單成立，尚未付款1:訂單付款，等待出貨2:訂單集貨中3:訂單已出貨4:訂單已完成-1:後台取消訂單');
                $table->integer('orginal_money')->nullable()->default(0)->comment('原始實收金額');
                $table->integer('return_money')->nullable()->default(0)->comment('退款');
                $table->integer('balance')->nullable()->default(0)->comment('餘額');
                $table->integer('total_item_quantity')->nullable()->default(0)->comment('商品數量');
                $table->integer('direct_ship_quantity')->nullable()->default(0)->comment('直寄商品數量');
                $table->date('vendor_arrival_date')->nullable()->comment('廠商預定到貨日');
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
        if (env('DB_MIGRATE_SYNCED_ORDERS')) {
            Schema::dropIfExists('synced_orders');
        }
    }
}
