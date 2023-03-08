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
use App\Models\User;
use App\Models\PoolEvent;
use App\Models\SpecialPoolEvent;
use App\Models\Notification;

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

        foreach($users as $user) {
            foreach($participations as $participation) {
                if($participation->user_id == $user->id){
                    $poolEvents = PoolEvent::where('pool_id', '=', $participation->pool_id)->get();
                    $specialPoolEvent = SpecialPoolEvent::where('pool_id', '=', $participation->pool_id)->get();

                    $resultsOfTheParticipation = json_decode($participation->teams_selected);

                    $numberOfHits = $this->checkNumberOfHits($resultsOfTheParticipation,$poolEvents);

                    if($specialPoolEvent[0]->home_result == $resultsOfTheParticipation[14]->home_result 
                        && $specialPoolEvent[0]->away_result == $resultsOfTheParticipation[14]->away_result){
                        $numberOfHits += 1;
                    }

                    switch ($numberOfHits) {
                        case 7:
                            $user->coins += 5000;
                            break;
                        case 8:
                            $user->coins += 7500;
                            break;
                        case 9:
                            $user->coins += 10000;
                            break;
                        case 10:
                            $user->coins += 12500;
                            break;
                        case 11:
                            $user->coins += 20000;
                            break;
                        case 12:
                            $user->coins += 30000;
                            break;
                        case 13:
                            $user->coins += 50000;
                            break;
                        case 14:
                            $user->coins += 100000;
                            break;
                        case 15:
                            $user->coins += 200000;
                            break;
                    }

                    $user->save();

                    if($numberOfHits< 7) {
                        $newNotification = new Notification();
                        $newNotification->user_id = $user->id;
                        $newNotification->text = "Perdiste en la quiniela: " . $participation->name . ' con un total de: ' . $numberOfHits.' aciertos'; 
                        $newNotification->type = "lose";
                        $newNotification->save();
                    }else if($numberOfHits >= 7){
                        $newNotification = new Notification();
                        $newNotification->user_id = $user->id;
                        $newNotification->text = "Ganaste en la quiniela: " . $participation->name . ' con un total de: ' . $numberOfHits.' aciertos'; 
                        $newNotification->type = "victory";
                        $newNotification->save();
                    }

                    return ResponseGenerator::generateResponse("OK", 200, null, ["Quiniela finalizada"]);
                }
            }
        }
    }

    public function checkNumberOfHits($resultsOfTheParticipation, $resultsOfThePool){
        $hits = 0;

        for ($i= 0; $i < count($resultsOfThePool); $i++) {
            if($resultsOfTheParticipation[$i]->result == $resultsOfThePool[$i]->result) {
                $hits += 1;
            }
        }

        return $hits;
    }
}
