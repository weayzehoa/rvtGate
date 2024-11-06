<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpecialVendorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_SPECIAL_VENDORS')) {
            Schema::create('special_vendors', function (Blueprint $table) {
                $table->id();
                $table->integer('vendor_id')->comment('商家 id');
                $table->string('code',10)->comment('代碼');
                $table->string('company')->nullable()->comment('公司名');
                $table->string('name')->nullable()->comment('名稱');
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
        if (env('DB_MIGRATE_SPECIAL_VENDORS')) {
            Schema::dropIfExists('special_vendors');
        }
    }
}
