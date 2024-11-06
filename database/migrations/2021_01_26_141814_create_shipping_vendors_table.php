<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShippingVendorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_SHIPPING_VENDORS')) {
            Schema::create('shipping_vendors', function (Blueprint $table) {
                $table->id();
                $table->string('name',40)->comment('名稱');
                $table->string('name_en',100)->nullable()->comment('英文名稱');
                $table->string('tel',40)->nullable()->comment('聯絡電話');
                $table->string('api_url',255)->nullable()->comment('API連結');
                $table->float('sort',11,1)->default(999999)->comment('排序');
                $table->boolean('is_foreign')->unsigned()->nullable()->default(0)->comment('國內外, 1:國外 0:國內');
                $table->timestamps();
                //使用軟刪除
                $table->softDeletes();
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
        if (env('DB_MIGRATE_SHIPPING_VENDORS')) {
            Schema::dropIfExists('shipping_vendors');
        }
    }
}
