<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderCancelExcludeProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_ORDER_CANCEL_EXCLUDE_PRODUCTS_TABLE')) {
            Schema::create('order_cancel_exclude_products', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('product_model_id')->comment('產品model_id');
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
        if (env('DB_MIGRATE_ORDER_CANCEL_EXCLUDE_PRODUCTS_TABLE')) {
            Schema::dropIfExists('order_cancel_exclude_products');
        }
    }
}
