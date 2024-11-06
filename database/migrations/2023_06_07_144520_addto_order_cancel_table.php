<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddtoOrderCancelTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_ADD_TO_ORDER_CANCEL_TABLE')) {
            Schema::table('order_cancels', function (Blueprint $table) {
                $table->bigInteger('vendor_shipping_no')->index()->nullable()->comment('商家出貨單號');
                $table->integer('old_ori_id')->index()->nullable()->comment('舊的訂單商品id');
                $table->integer('new_ori_id')->index()->nullable()->comment('新的訂單商品id');

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
        if (env('DB_MIGRATE_ADD_TO_ORDER_CANCEL_TABLE')) {
            Schema::table('order_cancels', function (Blueprint $table) {
                $table->dropColumn('vendor_shipping_no');
                $table->dropColumn('old_ori_id');
                $table->dropColumn('new_ori_id');
            });
        }
    }
}
