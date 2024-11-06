<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSellReturnsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_SELL_RETURNS')) {
            Schema::create('sell_returns', function (Blueprint $table) {
                $table->id();
                $table->string('type',10)->comment('類別:銷退/折讓');
                $table->bigInteger('order_id')->comment('訂單id');
                $table->bigInteger('order_number')->comment('訂單號碼');
                $table->bigInteger('return_no')->comment('銷退/折讓單號碼');
                $table->bigInteger('erp_return_no')->comment('erp銷退單號碼');
                $table->string('erp_return_type')->nullable()->comment('erp銷退單單別');
                $table->integer('price')->comment('未稅金額');
                $table->integer('tax')->comment('稅額');
                $table->string('memo')->comment('備註');
                $table->date('return_date')->nullable()->comment('銷退折讓日期');
                $table->integer('return_admin_id')->nullable()->comment('銷退處理人');
                $table->boolean('is_del')->nullable()->default(0)->comment('是否取消，0:否，1:是');
                $table->timestamps();
                // === 索引 ===
                $table->index('order_id');
                $table->index('return_no');
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
        if (env('DB_MIGRATE_SELL_RETURNS')) {
            Schema::dropIfExists('sell_returns');
        }
    }
}
