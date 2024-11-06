<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (env('DB_MIGRATE_SCHEDULES')) {
            Schema::create('schedules', function (Blueprint $table) {
                $table->id();
                $table->integer('admin_id')->comment('管理者id');
                $table->string('frequency')->comment('循環頻率');
                $table->string('name')->comment('名稱');
                $table->string('code')->comment('代號');
                $table->boolean('is_on')->default(0)->comment('0:否, 1:是');
                $table->dateTime('last_update_time')->nullable()->comment('最後同步時間');
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
        if (env('DB_MIGRATE_SCHEDULES')) {
            Schema::dropIfExists('schedules');
        }
    }
}
