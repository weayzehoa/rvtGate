<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSyncedOrderErrorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_SYNCED_ORDER_ERRORS')) {
            Schema::create('synced_order_errors', function (Blueprint $table) {
                $table->id();
                $table->integer('order_id')->comment('訂單id');
                $table->string('order_number',16)->comment('訂單編號');
                $table->integer('product_model_id')->comment('product model id');
                $table->string('sku')->comment('sku no');
                $table->string('digiwin_no')->comment('Digiwin No');
                $table->string('error')->comment('錯誤訊息');
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
        if (env('DB_MIGRATE_SYNCED_ORDER_ERRORS')) {
            Schema::dropIfExists('synced_order_errors');
        }
    }
}
