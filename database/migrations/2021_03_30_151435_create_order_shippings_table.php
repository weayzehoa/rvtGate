<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderShippingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_ORDER_SHIPPINGS')) {
            Schema::create('order_shippings', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('order_id')->comment('訂單id');
                $table->unsignedInteger('order_item_id')->nullable()->comment('訂單商品id');
                $table->string('express_way', 32)->nullable()->comment('物流方式');
                $table->string('express_no')->nullable()->comment('物流單號或其他備註');
                $table->timestamps();
                //使用軟刪除
                $table->softDeletes();
                // === 索引 ===
                $table->index('order_id');
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
        if (env('DB_MIGRATE_ORDER_SHIPPINGS')) {
            Schema::dropIfExists('order_shippings');
        }
    }
}
