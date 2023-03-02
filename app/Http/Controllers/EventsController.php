<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Helpers\ResponseGenerator;
use App\Models\Event;
use App\Models\Team;
use Illuminate\Support\Facades\Validator;

class EventsController extends Controller
{
    //BET-160
    public function create(Request $request){
        $json = $request->getContent();
        $data = json_decode($json);
    
        if($data){
            $rules = array(
                'home_id' => 'required|exists:teams,id',
                'away_id' => 'required|exists:teams,id',
                'home_odd' => 'required|numeric',
                'away_odd' => 'required|numeric',
                'tie_odd' => 'required|numeric',
                'date' => 'required|numeric',
                'finalDate' => 'required|numeric'
            );
        
            $customMessages = array(
                'home_id.required' => 'El id del equipo local es necesario',
                'home_id.exists:teams,id' => 'El id del equipo local debe existir en la tabla de equipos',
                'away_id.required' => 'El id del equipo visitante es necesario',
                'away_id.exists:teams,id' => 'El id del equipo visitante debe existir en la tabla de equipos',
                'home_odd.required' => 'La cuota del equipo local es necesaria',
                'home_odd.numeric' => 'La cuota del equipo local tiene que ser un número',
                'away_odd.required' => 'La cuota del equipo visitante es necesaria',
                'away_odd.numeric' => 'La cuota del equipo visitante tiene que ser un número',
                'tie_odd.required' => 'La cuota del empate es necesaria',
                'tie_odd.numeric' => 'La cuota del empate tiene que ser un número',
                'date.required' => 'La fecha del evento es necesaria',
                'date.numeric' => 'La fecha del evento tiene que ser un número',
                'finalDate.required' => 'La fecha del fin del evento es necesaria',
                'finalDate.numeric' => 'La fecha del fin del evento tiene que ser un número',
            );
            $validate = Validator::make(json_decode($json,true), $rules, $customMessages);
            if($validate->fails()){
                return ResponseGenerator::generateResponse("KO", 422, null, $validate->errors());
            }else{
                $event = new Event();
    
                $event->home_id = $data->home_id;
                $event->away_id = $data->away_id;
                $event->home_odd = $data->home_odd;
                $event->away_odd = $data->away_odd;
                $event->tie_odd = $data->tie_odd;
                $event->date = $data->date;
                $event->finalDate = $data->finalDate;
                $event->sport = $data->sport;
    
                try{
                    $event->save();
                    return ResponseGenerator::generateResponse("OK", 200, $event, ["Evento creado correctamente"]);
                }catch(\Exception $e){
                    return ResponseGenerator::generateResponse("KO", 304, $e, ["Error al crear Evento"]);
                }
            }
    
        }else{
            return ResponseGenerator::generateResponse("KO", 500, null, ["Datos no Registrados"]);
        }
    }

    //BET-74
    public function list(){
        $events = Event::with(['homeTeam','awayTeam'])->get();
        
        if($events){
            return ResponseGenerator::generateResponse("OK", 200, $events, ["Todos los eventos"]);
        }else{
            return ResponseGenerator::generateResponse("KO", 404, null, ["No se pueden devolver eventos"]);
        }
    }

    public function getEventById(Request $request){
        $json = $request->getContent();
        $data = json_decode($json);
    
        if($data){
            $rules = array(
                'id' => 'required|exists:events,id'
            );
        
            $customMessages = array(
                'id.required' => 'El id del evento es necesario',
                'id.exists:events,id' => 'El id del evento debe existir en la tabla de eventos',
            );
            $validate = Validator::make(json_decode($json,true), $rules, $customMessages);
            if($validate->fails()){
                return ResponseGenerator::generateResponse("KO", 422, null, $validate->errors());
            }else{
                try{
                    $event = Event::with(['homeTeam','awayTeam'])->where('id','=',$data->id)->get();
                    return ResponseGenerator::generateResponse("OK", 200, $event, ["Evento encontrado correctamente"]);
                }catch(\Exception $e){
                    return ResponseGenerator::generateResponse("KO", 304, $e, ["Error al buscar Evento"]);
                }
            }
    
        }else{
            return ResponseGenerator::generateResponse("KO", 500, null, ["Datos no Registrados"]);
        }
    }

    //BET-153
    public function participateInBet(Request $request) {
        $json = $request->getContent();

        $data = json_decode($json);

        if($data){
            $rules = array(
                'eventId' => 'required|integer|exists:events,id',
                'coins' => 'required|integer',
                'team_selected' => 'required|in:1,X,2'
            );
        
            $customMessages = array(
                'eventId.required' => 'La id del evento es necesaria',
                'eventId.integer' => 'La id del evento debe ser numérica',
                'eventId.exists:events,id' => 'La id del evento debe existir en la tabla de eventos',
                'coins.required' => 'Es necesario saber el número de monedas a apostar',
                'coins.integer' => 'Es necesario que las monedas sean numéricas',
                'team_selected.required' => 'Es necesario elegir un equipo ganador',
                'team_selected.in:1,X,2'  => 'Es necesario que sea 1, X, o 2'
            );
            $validate = Validator::make(json_decode($json,true), $rules, $customMessages);

            if($validate->fails()){
                return ResponseGenerator::generateResponse("OK", 422, null, $validate->errors()->all());
            }else{
                
                //Obtener el usuario a través del token
                $user = auth()->user();

                $event = Event::with(['homeTeam','awayTeam'])->where('id','=',$data->eventId)->get();
                $newEvent = json_encode($event);
                $newEvent = json_decode($event);

                
                $participations = DB::table('event_user')
                ->where('event_id','=', $event[0]->id)
                ->where('user_id', '=', $user->id)
                ->get();

                if(empty($event)){
                    return ResponseGenerator::generateResponse("KO", 422, null, ["Evento con esa id no encontrado"]);
                }else if(!count($participations) == 0){
                    return ResponseGenerator::generateResponse("KO", 422, null , ["Ya has participado en esta apuesta"]);
                }else if($user->coins < $data->coins){
                    return ResponseGenerator::generateResponse("KO", 422, null, ["No tienes las monedas suficientes para participar"]);
                }else{
                    try{
                        //Disminuir las monedas del usuario y crear la participación
                        $user->coins -= $data->coins;
                        $event[0]->users()->attach($user->id, ['coins' => $data->coins, 'team_selected'=>$data->team_selected]);
                        $user->save();

                        //Una vez creada la participación y guardado el usuario se crea la notificación
                        try {
                            $notification = new Notification();
                            $notification->text = "Has participado en el ". $newEvent[0]->home_team->name . " vs " . $newEvent[0]->away_team->name;
                            $notification->type = "participation";
                            $notification->user_id = $user->id;
                            $notification->save();
                            return ResponseGenerator::generateResponse("OK", 200, null, ["Participación creada"]);
                        }catch(\Exception $e){
                            return ResponseGenerator::generateResponse("KO", 405, $e, ["Error al crear la notificación"]);
                        }
                        
                    }catch(\Exception $e){
                        return ResponseGenerator::generateResponse("KO", 405, $e, ["Error al guardar la participación"]);
                    }
                }
            }
        }else{
            return ResponseGenerator::generateResponse("KO", 500, null, ["Datos no introducidos"]);
        }
    }
}
