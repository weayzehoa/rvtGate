<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddtoPurchaseOrderItemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_ADD_TO_PURCHASE_ORDER_ITEM_TABLE')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                $table->bigInteger('vendor_shipping_no')->nullable()->comment('商家出貨單號');
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
        if (env('DB_MIGRATE_ADD_TO_PURCHASE_ORDER_ITEM_TABLE')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                $table->dropColumn('vendor_shipping_no');
            });
        }
    }
}
