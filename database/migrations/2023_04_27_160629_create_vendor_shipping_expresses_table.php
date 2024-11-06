<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVendorShippingExpressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_VENDOR_SHIPPING_EXPRESSES_TABLE')) {
            Schema::create('vendor_shipping_expresses', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('shipping_no')->index()->comment('出貨單號');
                $table->integer('vsi_id')->comment('vendor_shipping_item id');
                $table->integer('poi_id')->nullable()->comment('purchase_order_item id');
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
        if (env('DB_MIGRATE_VENDOR_SHIPPING_EXPRESSES_TABLE')) {
            Schema::dropIfExists('vendor_shipping_expresses');
        }
    }
}
