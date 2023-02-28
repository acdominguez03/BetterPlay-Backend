<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResponseGenerator;
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
            $validate = Validator::make(json_decode($json,true), [
                'home_id' => 'required|exists:teams,id',
                'away_id' => 'required|exists:teams,id',
                'home_odd' => 'required|numeric',
                'away_odd' => 'required|numeric',
                'tie_odd' => 'required|numeric',
                'date' => 'required|numeric',
                'finalDate' => 'required|numeric'
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
                $event->finalDate = $data->finalDate;
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
}
