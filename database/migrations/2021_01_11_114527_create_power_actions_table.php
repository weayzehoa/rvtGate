<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePowerActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_POWER_ACTIONS')) {
            Schema::create('power_actions', function (Blueprint $table) {
                $table->id();
                $table->string('name')->comment('名稱');
                $table->string('code')->comment('代碼');
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
        if (env('DB_MIGRATE_POWER_ACTIONS')) {
            Schema::dropIfExists('power_actions');
        }
    }
}
