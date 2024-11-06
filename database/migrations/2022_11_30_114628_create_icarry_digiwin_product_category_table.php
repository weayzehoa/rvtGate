<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIcarryDigiwinProductCategoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_ICARRY_DIGIWIN_PRODUCT_CATEGORY')) {
            Schema::connection('icarry')->create('digiwin_product_category', function (Blueprint $table) {
                $table->id();
                $table->string('name')->comment('名稱');
                $table->string('code')->comment('代號');
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
        if (env('DB_MIGRATE_ICARRY_DIGIWIN_PRODUCT_CATEGORY')) {
            Schema::connection('icarry')->dropIfExists('digiwin_product_category');
        }
    }
}
