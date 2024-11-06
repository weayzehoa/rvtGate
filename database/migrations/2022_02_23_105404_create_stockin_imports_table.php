<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockinImportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_STOCKIN_IMPORTS')) {
            Schema::create('stockin_imports', function (Blueprint $table) {
                $table->id();
                $table->integer('import_no')->comment('匯入編號');
                $table->dateTime('warehouse_export_time')->comment('倉庫匯出時間');
                $table->string('warehouse_stockin_no')->comment('倉庫入庫單號');
                $table->integer('vendor_id')->comment('商家id');
                $table->string('gtin13')->comment('product model table gtin13');
                $table->string('product_name')->comment('商品名稱');
                $table->integer('expected_quantity')->nullable()->comment('預計交貨量');
                $table->integer('stockin_quantity')->nullable()->comment('入庫數量');
                $table->dateTime('stockin_time')->nullable()->comment('入庫時間');
                $table->longText('purchase_nos')->nullable()->comment('採購單號,以,隔開');
                $table->bigInteger('sell_no')->nullable()->comment('出貨單單號');
                $table->date('expiry_date')->nullable()->comment('到期日');
                $table->string('type',1)->nullable()->default('N')->comment('是否殘品');
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
        if (env('DB_MIGRATE_STOCKIN_IMPORTS')) {
            Schema::dropIfExists('stockin_imports');
        }
    }
}
