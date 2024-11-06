<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSellItemSinglesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_SELL_ITEM_SINGLES')) {
            Schema::create('sell_item_singles', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('sell_no')->comment('銷貨單單號');
                $table->bigInteger('erp_sell_no')->nullable()->comment('erp銷貨單單號');
                $table->string('erp_sell_sno',4)->nullable()->comment('erp銷貨單序號');
                $table->bigInteger('erp_order_no')->comment('erp訂單單號');
                $table->string('erp_order_sno',4)->nullable()->comment('erp訂單序號');
                $table->bigInteger('order_number')->nullable()->comment('訂單號碼');
                $table->integer('order_item_id')->nullable()->comment('訂單item id');
                $table->integer('order_item_package_id')->nullable()->comment('訂單item package id');
                $table->integer('order_quantity')->nullable()->default(0)->comment('訂單數量');
                $table->integer('sell_quantity')->nullable()->default(0)->comment('銷貨數量');
                $table->date('sell_date')->nullable()->comment('銷貨日期');
                $table->unsignedInteger('product_model_id')->nullable()->comment('商品 model id');
                $table->decimal('sell_price',10,2)->comment('銷貨金額');
                $table->string('memo')->nullable()->comment('備註');
                $table->boolean('direct_shipment')->nullable()->comment('是否直寄，0:否，1:是');
                $table->string('express_way')->nullable()->comment('物流商');
                $table->string('express_no')->nullable()->comment('物流單號');
                $table->boolean('is_del')->nullable()->default(0)->comment('是否取消，0:否，1:是');
                $table->bigInteger('purchase_no')->nullable()->comment('採購單單號');
                $table->integer('pois_id')->nullable()->comment('採購單singleDB id');
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
        if (env('DB_MIGRATE_SELL_ITEM_SINGLES')) {
            Schema::dropIfExists('sell_item_singles');
        }
    }
}
