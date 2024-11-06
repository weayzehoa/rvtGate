<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddtoOrderImportTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_ADD_TO_ORDER_IMPORT_TABLE')) {
            Schema::table('order_imports', function (Blueprint $table) {
                $table->boolean('invoice_type')->nullable()->comment('2:二聯式 3:三聯式');
                $table->boolean('invoice_sub_type')->nullable()->comment('1:捐贈 2:個人, 3:公司');
                $table->string('love_code',20)->nullable()->comment('愛心碼');
                $table->boolean('carrier_type')->nullable()->comment('不使用留空白, 0=手機條碼 1=自然人憑證 2=智付寶');
                $table->string('carrier_num',20)->nullable()->comment('載具資料');
                $table->string('invoice_title')->nullable()->comment('抬頭');
                $table->string('invoice_number')->nullable()->comment('統編');
                $table->string('buyer_name')->nullable()->comment('買受人');
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
        if (env('DB_MIGRATE_ADD_TO_ORDER_IMPORT_TABLE')) {
            Schema::table('order_imports', function (Blueprint $table) {
                $table->dropColumn('invoice_type');
                $table->dropColumn('invoice_sub_type');
                $table->dropColumn('love_code');
                $table->dropColumn('carrier_type');
                $table->dropColumn('carrier_num');
                $table->dropColumn('invoice_title');
                $table->dropColumn('invoice_number');
                $table->dropColumn('buyer_name');
            });
        }
    }
}
