<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Helpers\ResponseGenerator;
use App\Models\PoolParticipation;

class PoolCoinsDealer implements ShouldQueue
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
        $current_time = Carbon::now()->timestamp;

        $users = User::all();

        $participations = PoolParticipation::join('pools', 'pools.id', '=', 'pool_participations.pool_id')->get();

        $poolEvents = PoolEvent::all();

        $specialPoolEvents = SpecialPoolEvent::all();

        foreach($users as $user) {
            foreach($participations as $participation) {
                if($participation->sent == 0 && $participation->finalDate <= $current_time){
                    
                }
            }
        }
        
        
    }
}
