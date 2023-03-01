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

class UsersController extends Controller
{
    //BET-30
    public function login(Request $request){

        $json = $request->getContent();
    
        $data = json_decode($json);
    
        if($data){
    
            //validar datos
            $validate = Validator::make(json_decode($json,true), [
               'username' => 'required',
               'password' => 'required|min:6'
            ]);
            if($validate->fails()){
                return ResponseGenerator::generateResponse("KO", 422, null, $validate->errors()->all());
            }else{
                try{
                    $user = User::where('username', 'like', $data->username)->firstOrFail();
    
                    if(!Hash::check($data->password, $user->password)) {
                        return ResponseGenerator::generateResponse("KO", 404, null, ["Login incorrecto, comprueba la contraseña"]);
                    }else{
                        $user->tokens()->delete();
    
                        $token = $user->createToken($user->username);
                        return ResponseGenerator::generateResponse("OK", 200, $token->plainTextToken, ["Login correcto"]);
                    }
                }catch(\Exception $e){
                    return ResponseGenerator::generateResponse("KO", 404, null, ["Login incorrecto, usuario erróneo"]);
                }
            }
        }else{
            return ResponseGenerator::generateResponse("KO", 500, null, ["Datos no registrados"]);
        }
    }

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

    //BET-43
    public function sendMail(Request $request){
        $json = $request->getContent();
    
        $data = json_decode($json);
    
        if($data){
            $user = User::where('email', '=', $data->email)->first();
    
            if(!empty($user)){
                $code = random_int(100000, 999999);
                $user->code = "{$code}";
    
                try{
                    $user->save();
                    Mail::to($data->email)->send(new CodeMail($code));
                    return ResponseGenerator::generateResponse("OK", 200, null, ["Email enviado"]);
                }catch(\Exception $e){
                    return ResponseGenerator::generateResponse("KO", 405,null, ["Error al guardar el código del usuario"]);
                }
            }else{
                return ResponseGenerator::generateResponse("KO", 404, null, ["Usuario con ese correo no encontrado"]);
            } 
        }else{
            return ResponseGenerator::generateResponse("KO", 500, null, ["Datos incorrectos"]);
        }
    }
    
    public function checkCorrectSecretCode(Request $request){
        $json = $request->getContent();
    
        $data = json_decode($json);
    
        if($data){
            $validate = Validator::make(json_decode($json,true), [
                'id' => 'required|integer',
                'code' => 'required|string|min:6|max:6'
            ]);
    
            if($validate->fails()){
                return ResponseGenerator::generateResponse("KO", 422, null, $validate->errors()->all());
            }else{
                $user = User::find($data->id);
                if($user){
                    if($user->code == $data->code){
                        $user->code = null;
                        try{
                            $user->save();
                            return ResponseGenerator::generateResponse("OK", 200, null, ["Código correcto"]);
                        }catch(\Exception $e){
                            return ResponseGenerator::generateResponse("OK", 303, null, ["Error al borrar el código secreto"]);
                        }
                    }else{
                        return ResponseGenerator::generateResponse("KO", 400, null, ["Código erróneo"]);
                    }
                }else{
                    return ResponseGenerator::generateResponse("KO", 404, null, ["Usuario no encontrado"]);
                }
            }
        }else{
            return ResponseGenerator::generateResponse("KO", 500, null, ["Datos incorrectos"]);
        }
    }
    
    public function changePassword(Request $request){
        $json = $request->getContent();
    
        $data = json_decode($json);
    
        if($data){
            $validate = Validator::make(json_decode($json,true), [
                'id' => 'required|integer',
                'password' => 'required|string|min:6'
            ]);
    
            if($validate->fails()){
                return ResponseGenerator::generateResponse("KO", 422, null, $validate->errors());
            }else{
                $user = User::find($data->id);
                if($user){
                    $user->password = Hash::make($data->password);
                    try{
                        $user->save();
                        return ResponseGenerator::generateResponse("OK", 200, null, ["Contraseña cambiada"]);
                    }catch(\Exception $e){
                        return ResponseGenerator::generateResponse("OK", 303, null, ["Error al cambiar la contraseña"]);
                    }
                }else{
                    return ResponseGenerator::generateResponse("KO", 404, null, ["Usuario no encontrado"]);
                }
            }
        }else{
            return ResponseGenerator::generateResponse("KO", 500, null, ["Datos incorrectos"]);
        }
    }

    //BET-75
    public function edit(Request $request){
        $json = $request->getContent();
        $data = json_decode($json);
        
        if($data){
            $validate = Validator::make(json_decode($json, true),[
                'username' => 'string|unique:users',
                'password' => 'string|min:6',
                'photo' => 'nullable'
            ]);
    
            if($validate->fails()){
                return ResponseGenerator::generateResponse("KO", 422, null, $validate->errors()->all());
            }
            $user = auth()->user();
            $user->username = $data->username;
            $user->password = Hash::make($data->password);
            $image = str_replace('data:image/jpeg;base64,', '', $data->photo);
            $image = str_replace(' ', '+', $image);
            $imageName =$user->username.'.'.'jpeg';
            \File::put(storage_path(). '/' . $imageName, base64_decode($image));
            $ruta = storage_path(). '/' . $imageName;
            $user->photo = $ruta;
            try{
                $user->save();
                return ResponseGenerator::generateResponse("OK", 200, $ruta , ["Datos Actualizados correctamente"]);
            }catch(\Exception $e){
                return ResponseGenerator::generateResponse("KO", 404, null, ["No se han podido actualizar los datos"]);
            } 
        }else{
            return ResponseGenerator::generateResponse("KO", 500, null, ["Datos no registrados"]);
        }
    }

    public function getCurrentUserPhoto(){
        try{
            $user = auth()->user();
            if($user->photo != null){
                $image = base64_encode(file_get_contents(storage_path(). '/' . $user->username . '.' . 'png'));
                return ResponseGenerator::generateResponse("OK", 200, $image, "Usuario obtenido correctamente");
            }else{
                return ResponseGenerator::generateResponse("OK", 200, null, "Usuario obtenido correctamente");
            }
        }catch(\Exception $e){
            return ResponseGenerator::generateResponse("KO", 304, null, "Error al buscar");
        }
    }
}
