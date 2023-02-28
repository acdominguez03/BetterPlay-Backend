<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Helpers\ResponseGenerator;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\CodeMail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    //BET-22
    public function register(Request $request){
        $json = $request->getContent();
    
        $data = json_decode($json);
    
        if($data){
            $validate = Validator::make(json_decode($json,true), [
                'username' => 'required|string|unique:users,username',
                'email' => 'required|string|email|unique:users,email',
                'password' => 'required|string|min:6'
            ]);
    
            if($validate->fails()){
                return ResponseGenerator::generateResponse("KO", 422, null, $validate->errors()->all());
            }else {
                $user = new User();
    
                $user->username = $data->username;
                $user->email = $data->email;
                $user->password = Hash::make($data->password);
                $user->coins = 0;
                $user->followers = 0;
    
                try{
                    $user->save();
                    return ResponseGenerator::generateResponse("OK", 200, null, ["Usuario guardado correctamente"]);
                }catch(\Exception $e){
                    return ResponseGenerator::generateResponse("KO", 304, null, ["Error al guardar"]);
                }
            }
        }else{
            return ResponseGenerator::generateResponse("KO", 500, null, ["Datos no registrados"]);
        }
    }
}
