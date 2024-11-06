<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVendorShippingItemPackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_VENDOR_SHIPPING_ITEM_PACKAGES_TABLE')) {
            Schema::create('vendor_shipping_item_packages', function (Blueprint $table) {
                $table->id();
                $table->integer('vsi_id')->index()->comment('vendor shipping item id');
                $table->integer('poi_id')->index()->comment('purchase_order_item id');
                $table->integer('poip_id')->index()->comment('purchase_order_item_package id');
                $table->integer('product_model_id')->comment('商品 model id');
                $table->string('product_name')->comment('商品名稱');
                $table->string('digiwin_no')->comment('鼎新貨號');
                $table->integer('quantity')->comment('數量');
                $table->boolean('is_del')->nullable()->default(0)->comment('是否取消，0:否，1:是');
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
        if (env('DB_MIGRATE_VENDOR_SHIPPING_ITEM_PACKAGES_TABLE')) {
            Schema::dropIfExists('vendor_shipping_item_packages');
        }
    }
}
