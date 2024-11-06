<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnDiscountItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_RETURN_DISCOUNT_ITEMS')) {
            Schema::create('return_discount_items', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('return_discount_no')->comment('退貨折抵單單號');
                $table->string('erp_return_discount_no',20)->comment('erp退貨折抵單單號');
                $table->string('erp_return_discount_sno',20)->nullable()->comment('erp退貨折抵單流水號');
                $table->bigInteger('purchase_no')->nullable()->comment('採購單單號');
                $table->string('erp_purchase_type', 5)->nullable()->comment('erp採購單類別 A331, A332');
                $table->string('erp_purchase_no', 16)->nullable()->comment('erp採購單號');
                $table->string('erp_purchase_sno', 16)->nullable()->comment('erp採購單序號');
                $table->unsignedInteger('poi_id')->nullable()->comment('puchase order item id');
                $table->unsignedInteger('product_model_id')->comment('商品 model id');
                $table->decimal('purchase_price', 10, 2)->comment('採購金額');
                $table->integer('quantity')->comment('數量');
                $table->boolean('is_del')->nullable()->default(0)->comment('是否取消，0:否，1:是');
                $table->boolean('direct_shipment')->nullable()->default(0)->comment('是否直寄，0:否，1:是');
                $table->boolean('is_lock')->nullable()->default(0)->comment('是否鎖定，0:否，1:是');
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
        if (env('DB_MIGRATE_RETURN_DISCOUNT_ITEMS')) {
            Schema::dropIfExists('return_discount_items');
        }
    }
}
