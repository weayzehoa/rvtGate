<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMomoOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_MOMO_ORDERS')) {
            Schema::create('momo_orders', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('order_number')->comment('momo訂單號碼');
                $table->string('colE')->nullable()->comment('欄位E 訂單編號');
                $table->string('colF')->nullable()->comment('欄位F 收件人姓名');
                $table->string('colG')->nullable()->comment('欄位G 收件人地址');
                $table->string('colL')->nullable()->comment('欄位L 轉單日');
                $table->string('colM')->nullable()->comment('欄位M 預計出貨日');
                $table->string('colN')->nullable()->comment('欄位N 商品原廠編號 sku');
                $table->string('colP')->nullable()->comment('欄位P 品名');
                $table->string('colS')->nullable()->comment('欄位S 數量');
                $table->string('colU')->nullable()->comment('欄位U 售價(含稅)');
                $table->string('colW')->nullable()->comment('欄位U 訂購人姓名');
                $table->longText('all_cols')->nullable()->comment('所有欄位');
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
        if (env('DB_MIGRATE_MOMO_ORDERS')) {
            Schema::dropIfExists('momo_orders');
        }
    }
}
