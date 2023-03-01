<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Notification;
use App\Http\Helpers\ResponseGenerator;

class NotificationsController extends Controller
{
    public function getNotificationsByUser(){
        $user = auth()->user();

        try{
            $notifications = Notification::where('user_id', '=', $user->id)->get();
            return ResponseGenerator::generateResponse("OK", 200, $notifications, ["Notificaciones obtenidas"]);
        }catch(\Exception $e){
            return ResponseGenerator::generateResponse("KO", 500, $e, ["Error al obtener los datos"]);
        }
    }
    
    public function create(Request $request){
        $json = $request->getContent();
        $data = json_decode($json);
        if($data){
            $rules = array(
                'text' => 'required|string',
                'type' => 'required| in:participation,victory,lose,friendRequest'
            );
        
            $customMessages = array(
                'text.required' => 'El texto es necesario',
                'text.string' => 'El texto tiene que ser un string',
                'type.required' => 'El typo es necesario',
                'type.in:participation,victory,lose,friendRequest' => 'El tipo tiene que ser ono de los siguientes: participation, victory, lose o friendRequest'
            );
            $validate = Validator::make(json_decode($json,true), $rules, $customMessages);
           
            if($validate->fails()){
                return ResponseGenerator::generateResponse("KO", 422, null, $validate->errors());
            }else {
                $notification = new Notification();
                $notification->text = $data->text;
                $notification->type = $data->type;
                $notification->user_id = auth()->id();
                try{
                    $notification->save();
                    return ResponseGenerator::generateResponse("OK", 200, $notification, ["Notificación creada correctamente"]);
                }catch(\Exception $e){
                    return ResponseGenerator::generateResponse("KO", 304, $e, ["Error al crear"]);
                }
            }
        }else{
            return ResponseGenerator::generateResponse("KO", 500, null, ["Datos no registrados"]);
        }
    }
}
