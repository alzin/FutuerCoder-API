<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DeletePastCourseTimes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-past-course-times';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete past course times from the database';
    /*
    public function __construct()
    {
        parent::__construct();
    }
    */
    /**
     * Execute the console command.
     */
    public function handle()
    {/*
        $now = Carbon::now();

        DB::table('cources_times')
            ->where('SessionTimings', '<', $now->toDateString())
            ->orWhere(function ($query) use ($now) {
                $query->where('SessionTimings', '=', $now->toDateString())
                      ->where('endTime', '<', $now->toTimeString());
            })
            ->delete();

        $this->info('Past course times have been deleted successfully.');
        */
    }
}
