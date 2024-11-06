<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseExcludeProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_PURCHASE_EXCLUDE_PRODUCTS')) {
            Schema::create('purchase_exclude_products', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('product_model_id')->comment('商品model_id');
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
        if (env('DB_MIGRATE_PURCHASE_EXCLUDE_PRODUCTS')) {
            Schema::dropIfExists('purchase_exclude_products');
        }
    }
}
