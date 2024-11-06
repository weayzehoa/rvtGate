<?php

namespace App\Jobs\Schedule;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ExportCenter as ExportCenterDB;
use App\Models\Schedule as ScheduleDB;
use Carbon\Carbon;

class CleanExportFileScheduleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $destPath = storage_path('app/exports/');
        $exports = ExportCenterDB::where('created_at','<',Carbon::now()->subDays(3))->get();
        if(!empty($exports)){
            foreach($exports as $export){
                file_exists($destPath.$export->filename) ? unlink($destPath.$export->filename) : '';
                $export->delete();
            }
        }
        $schedule = ScheduleDB::where('code','cleanExportFile')->first()->update(['last_update_time' => date('Y-m-d H:i:s')]);
    }
}
