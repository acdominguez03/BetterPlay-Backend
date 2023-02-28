<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EventsController extends Controller
{
    //BET-160
    public function create(Request $request){
        $json = $request->getContent();
        $data = json_decode($json);
    
        if($data){
            $validate = Validator::make(json_decode($json,true), [
                'home_id' => 'required|exists:teams,id',
                'away_id' => 'required|exists:teams,id',
                'home_odd' => 'required|numeric',
                'away_odd' => 'required|numeric',
                'tie_odd' => 'required|numeric',
                'date' => 'required|numeric',
            ]);
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
                $event->sport = $data->sport;
    
                try{
                    $event->save();
                    return ResponseGenerator::generateResponse("OK", 200, $event, "Evento creado correctamente");
                }catch(\Exception $e){
                    return ResponseGenerator::generateResponse("KO", 304, $e, "Error al crear Evento");
                }
            }
    
        }else{
            return ResponseGenerator::generateResponse("KO", 500, null, "Datos no Registrados");
        }
    }

    //BET-74
    public function list(){
        $events = Event::with(['homeTeam','awayTeam'])->get();
        
        if($events){
            return ResponseGenerator::generateResponse("OK", 200, $events, "Todos los eventos");
        }else{
            return ResponseGenerator::generateResponse("KO", 404, null, "No se pueden devolver eventos");
        }
    }

    public function getEventById(Request $request){
        $json = $request->getContent();
        $data = json_decode($json);
    
        if($data){
            $validate = Validator::make(json_decode($json,true), [
                'id' => 'required|exists:events,id'
            ]);
            if($validate->fails()){
                return ResponseGenerator::generateResponse("KO", 422, null, $validate->errors());
            }else{
                try{
                    $event = Event::with(['homeTeam','awayTeam'])->where('id','=',$data->id)->get();
                    return ResponseGenerator::generateResponse("OK", 200, $event, "Evento encontrado correctamente");
                }catch(\Exception $e){
                    return ResponseGenerator::generateResponse("KO", 304, $e, "Error al buscar Evento");
                }
            }
    
        }else{
            return ResponseGenerator::generateResponse("KO", 500, null, "Datos no Registrados");
        }
    }

    //BET-153
    public function participateInBet(Request $request) {
        $json = $request->getContent();

        $data = json_decode($json);

        if($data){
            $validate = Validator::make(json_decode($json,true), [
                'eventId' => 'required|integer|exists:events,id',
                'userId' => 'required|integer|exists:users,id',
                'money' => 'required|integer',
                'winner' => 'required|in:home,tie,away'
            ]);
            if($validate->fails()){
                return ResponseGenerator::generateResponse("OK", 422, null, $validate->errors()->all());
            }else{
                $event = Event::find($data->eventId);

                if(!empty($event)){
                    try{
                        $event->users()->attach($data->userId, ['money' => $data->money, 'winner'=>$data->winner]);
                        $user = User::find($data->userId);
                        $user->coins -= $data->money;
                        $user->save();
                        return ResponseGenerator::generateResponse("OK", 200, null, ["Participación creada"]);
                    }catch(\Exception $e){
                        $event->users()->detach($data->userId);
                        return ResponseGenerator::generateResponse("KO", 405, $e, ["Error al guardar la participación"]);
                    }
                }else{
                    return ResponseGenerator::generateResponse("KO", 422, null, ["Evento con esa id no encontrada"]);
                }
            }
        }else{
            return ResponseGenerator::generateResponse("KO", 500, null, ["Datos no introducidos"]);
        }
    }
}
