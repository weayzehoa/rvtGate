<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderImportAbnormalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_ORDER_IMPORT_ABNORMALS')) {
            Schema::create('order_import_abnormals', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('order_import_id')->comment('匯入號碼id');
                $table->bigInteger('import_no')->comment('匯入號碼');
                $table->string('type')->comment('匯入類別');
                $table->string('partner_order_number')->comment('客戶訂單號碼');
                $table->string('sku')->comment('品號');
                $table->integer('quantity')->nullable()->comment('數量');
                $table->decimal('price',10,2)->nullable()->comment('單價');
                $table->string('memo')->nullable()->comment('異常原因');
                $table->string('row_no')->nullable()->comment('第幾筆錯誤');
                $table->boolean('is_chk')->nullable()->default(0)->comment('是否處理，0:否，1:是');
                $table->dateTime('chk_date')->nullable()->comment('處理日期');
                $table->integer('admin_id')->nullable()->comment('處理人');
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
        if (env('DB_MIGRATE_ORDER_IMPORT_ABNORMALS')) {
            Schema::dropIfExists('order_import_abnormals');
        }
    }
}
