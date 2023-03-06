<?php 

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

use Illuminate\Support\Facades\DB;
use App\Http\Helpers\ResponseGenerator;
use App\Models\User;
use App\Models\Event;
use App\Models\Notification;
use App\Models\Participation;

class CoinsDealer implements ShouldQueue
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

        $participations = Event::join('participations', 'events.id', '=', 'participations.event_id')
        ->join('teams as homeTeam', 'events.home_id', '=', 'homeTeam.id')
        ->join('teams as awayTeam', 'events.away_id', '=', 'awayTeam.id')
        ->select('events.winner', 'events.finalDate', 'events.home_odd','events.away_odd','events.tie_odd','participations.id AS participationId', 'participations.sent', 'participations.team_selected','participations.user_id','participations.coins' ,'homeTeam.name AS homeName', 'awayTeam.name AS awayName')
        ->get();
        
        foreach($users as $user) {
            foreach($participations as $participation) {
                if($participation->sent == 0 && $participation->finalDate <= $current_time){
                    if($participation->user_id == $user->id && $participation->team_selected == $participation->winner){
                        $newNotification = new Notification();
                        $newNotification->user_id = $participation->user_id;
                        $newNotification->text = "Ganaste en el evento: " . $participation->homeName . ' vs ' . $participation->awayName;
                        $newNotification->type = "victory";
                        $newNotification->save();
     
                        if($participation->team_selected == '1'){
                            $user->coins = $user->coins + ($participation->coins * $participation->home_odd);
                            $user->save();
                        }else if($participation->team_selected == '2'){
                            $user->coins = $user->coins + ($participation->coins * $participation->away_odd);
                            $user->save();
                        }else if($participation->team_selected == 'X'){
                            $user->coins = $user->coins + ($participation->coins * $participation->tie_odd);
                            $user->save();
                        }

                        $updateParticipation = Participation::find($participation->participationId);
                        if($updateParticipation) {
                            $updateParticipation->result = "victory";
                            $updateParticipation->sent = true;
                            $updateParticipation->save();
                        }
    
                    }else if ($participation->user_id != $user->id && $participation->team_selected != $participation->winner){
                        $newNotification = new Notification();
                        $newNotification->user_id = $participation->user_id;
                        $newNotification->text = "Perdiste en el evento: " . $participation->homeName . ' vs ' . $participation->awayName;
                        $newNotification->type = "lose";
                        $newNotification->save();

                        $updateParticipation = Participation::find($participation->participationId);

                        if($updateParticipation) {
                            $updateParticipation->result = "lose";
                            $updateParticipation->sent = true;
                            $updateParticipation->save();
                        }
                    }
                }
            }
        }
    }
}
