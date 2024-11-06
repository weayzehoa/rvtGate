<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSfShippingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_SF_SHIPPINGS_TABLE')) {
            Schema::create('sf_shippings', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('vendor_shipping_no')->index()->comment('商家出貨單號');
                $table->string('sf_express_no')->index()->comment('順豐單號');
                $table->string('invoice_url',500)->nullable()->comment('發票url');
                $table->string('label_url',500)->nullable()->comment('列印url');
                $table->integer('vendor_id')->index()->comment('商家代號');
                $table->integer('fee')->nullable()->comment('費用');
                $table->integer('sno')->nullable()->comment('序號');
                $table->string('phone')->nullable()->comment('電話');
                $table->date('vendor_arrival_date')->comment('廠商應到貨日');
                $table->date('shipping_date')->nullable()->comment('出貨日');
                $table->date('stockin_date')->nullable()->comment('入庫日');
                $table->boolean('status')->nullable()->default(0)->comment('-1:作廢 0:待出貨, 1:已送達');
                $table->string('trace_address',500)->nullable()->comment('包裹位置');
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
        if (env('DB_MIGRATE_SF_SHIPPINGS_TABLE')) {
            Schema::dropIfExists('sf_shippings');
        }
    }
}
