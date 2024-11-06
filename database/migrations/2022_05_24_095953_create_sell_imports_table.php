<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSellImportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_SELL_IMPORTS')) {
            Schema::create('sell_imports', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('order_id')->nullable()->comment('訂單id');
                $table->bigInteger('import_no')->comment('匯入號碼');
                $table->bigInteger('order_number')->nullable()->comment('訂單號碼');
                $table->string('type')->comment('類別');
                $table->string('shipping_number')->nullable()->comment('物流單號');
                $table->string('gtin13')->nullable()->comment('gtin13國際條碼');
                $table->string('purchase_no')->nullable()->comment('採購單號');
                $table->string('digiwin_no')->nullable()->comment('鼎新貨號');
                $table->string('product_name')->nullable()->comment('商品名稱');
                $table->integer('quantity')->nullable()->default(0)->comment('數量');
                $table->date('sell_date')->nullable()->comment('銷貨日期');
                $table->dateTime('stockin_time')->nullable()->comment('廠商到貨日');
                $table->boolean('status')->nullable()->default(0)->comment('狀態');
                $table->string('memo',500)->nullable()->comment('說明');
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
        if (env('DB_MIGRATE_SELL_IMPORTS')) {
            Schema::dropIfExists('sell_imports');
        }
    }
}
