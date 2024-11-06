<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVendorShippingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_VENDOR_SHIPPINGS_TABLE')) {
            Schema::create('vendor_shippings', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('shipping_no')->index()->comment('出貨單號');
                $table->integer('vendor_id')->index()->comment('商家代號');
                $table->date('vendor_arrival_date')->comment('廠商應到貨日');
                $table->date('shipping_finish_date')->nullable()->comment('最後一筆出貨日');
                $table->date('stockin_finish_date')->nullable()->comment('已完成入庫日');
                $table->boolean('status')->nullable()->default(0)->comment('-1:取消 0:待出貨, 1:已出貨');
                $table->boolean('method')->nullable()->default(0)->comment('0:未選擇, 1:順豐運單, 2:自行填入');
                $table->string('memo')->nullable()->comment('商家備註');
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
        if (env('DB_MIGRATE_VENDOR_SHIPPINGS_TABLE')) {
            Schema::dropIfExists('vendor_shippings');
        }
    }
}
