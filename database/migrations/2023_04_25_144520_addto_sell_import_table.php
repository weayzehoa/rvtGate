<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddtoSellImportTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_ADD_TO_SELL_IMPORT_TABLE')) {
            Schema::table('sell_imports', function (Blueprint $table) {
                $table->integer('vsi_id')->nullable()->comment('vendor shipping item id');
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
        if (env('DB_MIGRATE_ADD_TO_SELL_IMPORT_TABLE')) {
            Schema::table('sell_imports', function (Blueprint $table) {
                $table->dropColumn('vsi_id');
            });
        }
    }
}
