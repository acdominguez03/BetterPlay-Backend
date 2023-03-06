<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Helpers\ResponseGenerator;
use Illuminate\Support\Facades\Validator;
use App\Models\Pool;
use Carbon\Carbon;

class PoolsController extends Controller
{
    public function create(Request $request){
        $json = $request->getContent();
        $data = json_decode($json);
    
        if($data){
            $rules = array(
                'name' => 'required|string|unique:pools,name',
                'matches' => 'required|array|min:15',
                'finalDate' => 'required|numeric'
            );
        
            $customMessages = array(
                'name.required' => 'El nombre de la Quiniela es necesario',
                'name.string' => 'El nombre de la Quiniela tiene que ser un string',
                'name.unique:pools,name' => 'El nombre de la Quiniela tiene que ser único en la tabla de Usuarios',
                'matches.required' => 'El json de los Partidos es necesario',
                'matches.array' => 'El grupo de partidos tiene que ser un Array',
                'matches.min:15'=> 'El array tiene que ser de longitud 15',
                'finalDate.required' => 'La fecha del final de la Quiniela es necesaria',
                'finalDate.numeric' => 'La Fecha final tiene que ser un número'
            );
            $validate = Validator::make(json_decode($json,true), $rules, $customMessages);
            if($validate->fails()){
                return ResponseGenerator::generateResponse("KO", 422, null, $validate->errors()->all());
            }else {
                $pool = new Pool();
    
                $pool->name = $data->name;
                $pool->matches = json_encode($data->matches);
                $pool->finalDate = $data->finalDate;
                try{
                    $pool->save();
                    return ResponseGenerator::generateResponse("OK", 200, null, ["Quiniela guardada correctamente"]);
                }catch(\Exception $e){
                    return ResponseGenerator::generateResponse("KO", 304, $e, ["Error al guardar"]);
                }
            }
        }else{
            return ResponseGenerator::generateResponse("KO", 500, null, ["Datos no registrados"]);
        }
    }

}
