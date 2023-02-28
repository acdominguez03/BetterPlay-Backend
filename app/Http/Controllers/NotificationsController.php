<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    public function create(Request $request){
        $json = $request->getContent();
        $data = json_decode($json);
        if($data){
            $validate = Validator::make(json_decode($json,true), [
                'text' => 'required|string',
                'type' => 'required| in:participation,victory,lose,friendRequest'
            ]);
           
            if($validate->fails()){
                return ResponseGenerator::generateResponse("KO", 422, null, $validate->errors());
            }else {
                $notification = new Notification();
                $notification->text = $data->text;
                $notification->type = $data->type;
                $notification->user_id = auth()->id();
                try{
                    $notification->save();
                    return ResponseGenerator::generateResponse("OK", 200, $notification, "Notificación creada correctamente");
                }catch(\Exception $e){
                    return ResponseGenerator::generateResponse("KO", 304, $e, "Error al crear");
                }
            }
        }else{
            return ResponseGenerator::generateResponse("KO", 500, null, "Datos no registrados");
        }
    }
}
