<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSfShippingLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_SF_SHIPPING_LOGS_TABLE')) {
            Schema::create('sf_shipping_logs', function (Blueprint $table) {
                $table->id();
                $table->string('type')->nullable()->comment('類型');
                $table->longText('headers')->nullable();
                $table->longText('post_json')->nullable();
                $table->longText('get_json')->nullable();
                $table->integer('rtnCode')->nullable()->comment('返回代碼');
                $table->string('rtnMsg')->nullable()->comment('返回訊息');
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
        if (env('DB_MIGRATE_SF_SHIPPING_LOGS_TABLE')) {
            Schema::dropIfExists('sf_shipping_logs');
        }
    }
}
