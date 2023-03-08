<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Helpers\ResponseGenerator;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\CodeMail;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Info(
 *      version="1.0.0", 
 *      title="BetterPlay API",
 *      description="Aquí podrás ver todas las funciones de la API detalladamente",
 * )
 */
class UsersController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/users/login",
     *     tags={"users"},
     *     summary="Logea un Usuario",
     *     description="Recibe el nombre y la contraseña de un usuario, y le asigna un token",
     *     @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                      type="object",
     *                      @OA\Property(
     *                          property="username",
     *                          type="string"
     *                      ),
     *                      @OA\Property(
     *                          property="password",
     *                          type="string"
     *                      ),
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Logeado Correctamente"
     *     ),
     *     @OA\Response(
     *         response="304",
     *         description="Error al guardar",
     *     )
     * )
     */
    //BET-30
    public function login(Request $request){

        $json = $request->getContent();
        $data = json_decode($json);
    
        if($data){
            $rules = array(
                'username' => 'required|string',
                'password' => 'required|string|min:6'
            );
        
            $customMessages = array(
                'username.required' => 'El nombre de Usuario es necesario',
                'password.required' => 'La contraseña es necesaria',
                'password.string' => 'La contraseña tiene que ser un string',
                'password.min:6' => 'La contraseña una longitud mínima de 6'
            );
    
            //validar datos
            $validate = Validator::make(json_decode($json,true), $rules, $customMessages);
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
                        return ResponseGenerator::generateResponse("OK", 200, ['token'=> $token->plainTextToken,'user'=> $user], ["Login correcto"]);
                    }
                }catch(\Exception $e){
                    return ResponseGenerator::generateResponse("KO", 404, null, ["Login incorrecto, usuario erróneo"]);
                }
            }
        }else{
            return ResponseGenerator::generateResponse("KO", 500, null, ["Datos no registrados"]);
        }
    }
    /**
     * @OA\Put(
     *     path="/api/users/register",
     *     tags={"users"},
     *     summary="Registra un Usuario",
     *     description="Recibe el nombre, el correo y la contraseña y añade un usuario a la tabla de usuarios",
     *     @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                      type="object",
     *                      @OA\Property(
     *                          property="username",
     *                          type="string"
     *                      ),
     *                      @OA\Property(
     *                          property="email",
     *                          type="string"
     *                      ),
     *                      @OA\Property(
     *                          property="password",
     *                          type="string"
     *                      ),
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Creado Correctamente"
     *     ),
     *     @OA\Response(
     *         response="304",
     *         description="Error al guardar",
     *     )
     * )
     */
    //BET-22
    public function register(Request $request){
        $json = $request->getContent();
        $data = json_decode($json);
    
        if($data){
            $rules = array(
                'username' => 'required|string|unique:users,username',
                'email' => 'required|string|email|unique:users,email',
                'password' => 'required|string|min:6'
            );
        
            $customMessages = array(
                'username.required' => 'El nombre de Usuario es necesario',
                'username.string' => 'El nombre de Usuario tiene que ser un string',
                'username.unique:users,username' => 'El nombre de Usuario tiene que ser único en la tabla de Usuarios',
                'email.required' => 'El email del Usuario es necesario',
                'email.string' => 'El email tiene que ser un string',
                'email.email' => 'El email tiene que cumplir el formato email',
                'email.unique:users,email' => 'El email debe ser único en la tabala de Usuarios',
                'password.required' => 'La contraseña es necesaria',
                'password.string' => 'La contraseña tiene que ser un string',
                'password.min:6' => 'La contraseña una longitud mínima de 6'
            );
            $validate = Validator::make(json_decode($json,true), $rules, $customMessages);
    
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
    /**
     * @OA\Post(
     *     path="/api/users/sendEmail",
     *     tags={"users"},
     *     @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                      type="object",
     *                      @OA\Property(
     *                          property="email",
     *                          type="string"
     *                      ),
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email enviado Correctamente"
     *     ),
     *     @OA\Response(
     *         response="405",
     *         description="Error al guardar el código del usuario",
     *     )
     * )
     */
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
                    return ResponseGenerator::generateResponse("OK", 200, $user->id,  ["Email enviado"]);
                }catch(\Exception $e){
                    return ResponseGenerator::generateResponse("KO", 405,$e, ["Error al guardar el código del usuario"]);
                }
            }else{
                return ResponseGenerator::generateResponse("KO", 404, null, ["Usuario con ese correo no encontrado"]);
            } 
        }else{
            return ResponseGenerator::generateResponse("KO", 500, null, ["Datos incorrectos"]);
        }
    }
    /**
     * @OA\Post(
     *     path="/api/users/checkCorrectSecretCode",
     *     tags={"users"},
     *     summary="Comprueba el código secreto",
     *     description="Recibe el código secreto",
     *     @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                      type="object",
     *                      @OA\Property(
     *                          property="id",
     *                          type="integer"
     *                      ),
     *                      @OA\Property(
     *                          property="code",
     *                          type="string"
     *                      ),
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Código Correcto Correctamente"
     *     ),
     * )
     */
    public function checkCorrectSecretCode(Request $request){
        $json = $request->getContent();
        $data = json_decode($json);
    
        if($data){
            $rules = array(
                'id' => 'required|integer',
                'code' => 'required|string|min:6|max:6'
            );
        
            $customMessages = array(
                'id.required' => 'La id del usuario es necesaria',
                'code.required' => 'Necesitamos saber el código de recuperación',
                'code.string' => 'El código tiene que ser un string',
                'code.min:6' => 'El código tiene una longitud mínima de 6',
                'code.max:6' => 'El código tiene una longitud máxima de 6'
            );

            $validate = Validator::make(json_decode($json,true), $rules, $customMessages);

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
    /**
     * @OA\Post(
     *     path="/api/users/changePassword",
     *     tags={"users"},
     *     summary="Cambia la contraseña",
     *     description="Recibe la nueva contrseña y la actualiza en el usuario",
     *     @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                      type="object",
     *                      @OA\Property(
     *                          property="id",
     *                          type="integer"
     *                      ),
     *                      @OA\Property(
     *                          property="password",
     *                          type="string"
     *                      ),
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Código Correcto Correctamente"
     *     ),
     * )
     */
    public function changePassword(Request $request){
        $json = $request->getContent();
        $data = json_decode($json);
    
        if($data){
            $rules = [
                'id' => 'required|integer',
                'password' => 'required|string|min:6'
            ];
        
            $customMessages = [
                'id.required' => 'La id del usuario es necesaria',
                'password.required' => 'Necesitamos saber la nueva contraseña',
                'password.string' => 'La contraseña tiene que ser un string',
                'password.min:6' => 'La contraseña tiene una longitud mínima de 6'
            ];

            $validate = Validator::make(json_decode($json,true), $rules, $customMessages);
            
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
    /**
     * @OA\Post(
     *     path="/api/users/edit",
     *     tags={"users"},
     *     summary="Actualiza los datos del usuario",
     *     description="Recibe un nombre de usuario, una contraseña y una foto y actualiza los datos del usuario",
     *     @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                      type="object",
     *                      @OA\Property(
     *                          property="username",
     *                          type="string"
     *                      ),
     *                      @OA\Property(
     *                          property="password",
     *                          type="string"
     *                      ),
     *                      @OA\Property(
     *                          property="photo",
     *                          type="string"
     *                      ),
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Actualizado Correctamente"
     *     ),
     * )
     */
    //BET-75
    public function edit(Request $request){
        $json = $request->getContent();
        $data = json_decode($json);
        
        if($data){
            $rules = array(
                'username' => 'required|string|unique:users,username',
                'password' => 'required|string|min:6',
                'photo' => 'nullable|string'
            );
        
            $customMessages = array(
                'username.required' => 'El nombre de Usuario es necesario',
                'username.string' => 'El nombre de Usuario tiene que ser un string',
                'username.unique:users,username' => 'El nombre de Usuario tiene que ser único en la tabla de Usuarios',
                'password.required' => 'La contraseña es necesaria',
                'password.string' => 'La contraseña tiene que ser un string',
                'password.min:6' => 'La contraseña una longitud mínima de 6'
            );
            $validate = Validator::make(json_decode($json, true),$rules, $customMessages);
    
            if($validate->fails()){
                return ResponseGenerator::generateResponse("KO", 422, null, $validate->errors()->all());
            }
            $user = auth()->user();
            $user->username = $data->username;
            $user->password = Hash::make($data->password);
            // $image = str_replace('data:image/jpeg;base64,', '', $data->photo);
            // $image = str_replace(' ', '+', $image);
            // $imageName =$user->username.'.'.'jpeg';
            // \File::put(storage_path(). '/' . $imageName, base64_decode($image));
            // $ruta = storage_path(). '/' . $imageName;
            $user->photo = $data->photo;
            try{
                $user->save();
                return ResponseGenerator::generateResponse("OK", 200, null , ["Datos Actualizados correctamente"]);
            }catch(\Exception $e){
                return ResponseGenerator::generateResponse("KO", 404, null, ["No se han podido actualizar los datos"]);
            } 
        }else{
            return ResponseGenerator::generateResponse("KO", 500, null, ["Datos no registrados"]);
        }
    }
    /**
     * @OA\Post(
     *     path="/api/users/updateStreak",
     *     tags={"users"},
     *     summary="Actualiza la racha de inicio de sesión",
     *     description="Recibe la fecha de logeo del usuario y comprueba los dias que han pasado desde la última vez que se conectó a la app y le asigna las monedas correspondientes",
     *     @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                      type="object",
     *                      @OA\Property(
     *                          property="date",
     *                          type="integer"
     *                      ),
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Actualizado Correctamente"
     *     ),
     * )
     */
    //BET-133
    public function updateStreak(Request $request){
        $json = $request->getContent();
        $data = json_decode($json);
    
        if($data){
            $rules = array(
                'date' => 'required|integer'
            );
        
            $customMessages = array(
                'date.required' => 'La fecha es necesaria',
                'date.integer' => 'La fecha debe ser un Integer'
            );
            $validate = Validator::make(json_decode($json,true),$rules, $customMessages);
            if($validate->fails()){
                return ResponseGenerator::generateResponse("KO", 422, null, $validate->errors());
            }else{
                $user = auth()->user();
                $streak = 0;
                $requestDate = date('Y-m-d H:i:s', $request->date);
    
                if($user->streakStartDate == null){
                    $user->streakStartDate = $requestDate;
                    $user->streakEndDate = $requestDate;
                    $user->coins += 500; 
    
                    try {
                        $user->save();
                        $streak = 1;
                        return ResponseGenerator::generateResponse("OK", 200, $streak, ["Racha actualizada a 1"]);
                    }catch(\Exception $e){
                        return ResponseGenerator::generateResponse("KO", 405, $e, ["Error al guardar la fecha de inicio de racha"]);
                    }
    
                }else if($this->checkDatesDiff($user->streakEndDate, $requestDate) < 1){
                    return ResponseGenerator::generateResponse("OK", 200, null, ["No hay aumento de racha"]);
                }else if($this->checkDatesDiff($user->streakEndDate, $requestDate) > 1){
                    $user->streakStartDate = $requestDate;
                    $user->streakEndDate = $requestDate;
                    $user->coins += 500; 
    
                    try {
                        $user->save();
                        $streak = 0;
                        return ResponseGenerator::generateResponse("OK", 200, $streak, ["Fin de la racha, racha actualizada a 1"]);
                    }catch(\Exception $e){
                        return ResponseGenerator::generateResponse("KO", 405, $e, ["Error al guardar la fecha de inicio de racha"]);
                    }
    
                }else if($this->checkDatesDiff($user->streakStartDate, $requestDate) == 1){
                    $user->streakEndDate = $requestDate;
                    $user->coins += 1000; 
                    try {
                        $user->save();
                        $streak = 2;
                        return ResponseGenerator::generateResponse("OK", 200, $streak, ["Racha actualizada a 2"]);
                    }catch(\Exception $e){
                        return ResponseGenerator::generateResponse("KO", 405, $e, ["Error al guardar la fecha de inicio de racha"]);
                    }
                }else if($this->checkDatesDiff($user->streakStartDate, $requestDate) == 2){
                    $user->streakEndDate = $requestDate;
                    $user->coins += 1500;
                    try {
                        $user->save();
                        $streak = 3;
                        return ResponseGenerator::generateResponse("OK", 200, $streak, ["Racha actualizada a 3"]);
                    }catch(\Exception $e){
                        return ResponseGenerator::generateResponse("KO", 405, $e, ["Error al guardar la fecha de inicio de racha"]);
                    }
                }else if($this->checkDatesDiff($user->streakStartDate, $requestDate) == 3){
                    $user->streakEndDate = $requestDate;
                    $user->coins += 2000;
                    try {
                        $user->save();
                        $streak = 4;
                        return ResponseGenerator::generateResponse("OK", 200, $streak, ["Racha actualizada a 4"]);
                    }catch(\Exception $e){
                        return ResponseGenerator::generateResponse("KO", 405, $e, ["Error al guardar la fecha de inicio de racha"]);
                    }
                }else if($this->checkDatesDiff($user->streakStartDate, $requestDate) >= 4){
                    $user->streakEndDate = $requestDate;
                    $user->coins += 2500;
                    try {
                        $user->save();
                        $streak = 5;
                        return ResponseGenerator::generateResponse("OK", 200, $streak, ["Racha máxima"]);
                    }catch(\Exception $e){
                        return ResponseGenerator::generateResponse("KO", 405, $e,["Error al guardar la fecha de inicio de racha"]);
                    }
                }
            }
        }else{
            return ResponseGenerator::generateResponse("KO", 500, null, ["Datos incorrectos"]);
        }
    }

    public function checkDatesDiff($initDate, $finishDate){
        $date1= new DateTime($initDate);
        $date2= new DateTime($finishDate);
        $diff = $date1->diff($date2);
        return $diff->days;
    }

    /**
     * @OA\Get(
     *     path="/api/users/getUserById",
     *     tags={"users"},
     *     summary="Busca a un usuario concreto",
     *     description="Obtiene una id de un usuario y lo busca en la tabla de usuarios ",
     *     @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                      type="object",
     *                      @OA\Property(
     *                          property="id",
     *                          type="integer"
     *                      ),
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuario Encontrado Correctamente"
     *     ),
     * )
     */
    //BET-121
    public function getUserById(Request $request){
        $json = $request->getContent();
    
        $data = json_decode($json);
    
        if($data){
            $rules = array(
                'id' => 'required|exists:users,id'
            );
        
            $customMessages = array(
                'id.required' => 'El id de Usuario es necesario',
                'id.exists:users,id' => 'Debe existir un usuario con ese Id'
            );
            $validate = Validator::make(json_decode($json,true),$rules, $customMessages);
    
            if($validate->fails()){
                return ResponseGenerator::generateResponse("KO", 422, null, $validate->errors()->all());
            }else {
                try{
                    $user = User::find($data->id);
                    return ResponseGenerator::generateResponse("OK", 200, $user, ["Usuario obtenido correctamente"]);
                }catch(\Exception $e){
                    return ResponseGenerator::generateResponse("KO", 304, null, ["Error al buscar"]);
                }
            }
        }else{
            return ResponseGenerator::generateResponse("KO", 500, null, ["Datos no registrados"]);
        }
    }
    /**
     * @OA\Get(
     *     path="/api/users/list",
     *     tags={"users"},
     *     summary="Devuelve todos los usuarios registrados",
     *     description="Devuelve una lista con todos los usuarios de la aplicación",
     *     @OA\Response(
     *         response=200,
     *         description="Usuario Encontrado Correctamente"
     *     ),
     * )
     */
    //BET-115
    public function list(){
        try{
            $users = User::all();
            return ResponseGenerator::generateResponse("OK", 200, $users, ["Usuarios obtenidos correctamente"]);
        }catch(\Exception $e){
            return ResponseGenerator::generateResponse("KO", 304, null, ["Error al obtener Usuarios"]);
        }
    }
    /**
     * @OA\Get(
     *     path="/api/users/getUserData",
     *     tags={"users"},
     *     summary="Devuelve todos los datos del usuario",
     *     description="Devuelve ulos datos de un usuario junto con las apuestas que ha realizado",
     *     @OA\Response(
     *         response=200,
     *         description="Usuario Encontrado Correctamente"
     *     ),
     * )
     */
    public function getUserData(){
        $user = auth()->user();

        $allParticipations = DB::table('event_user')
        ->join('events', 'event_user.event_id', '=', 'events.id')
        ->where('user_id', '=', $user->id)
        ->select('event_user.*', 'events.sport')
        ->get();

        $streak = DB::table('event_user')
        ->join('events', 'event_user.event_id', '=', 'events.id')
        ->where('user_id', '=', $user->id)
        ->orderBy('event_user.id', 'desc')->take(5)
        ->select('event_user.result')
        ->get();

        $finalStats = [
            'soccerVictory' => [],
            'basketballVictory' => [],
            'tennisVictory' => [],
            'soccer' => [],
            'basketball' => [],
            'tennis' => [],
        ];

        $percentage = [
            'soccer' => 0,
            'basketball' => 0,
            'tennis' => 0
        ];

        foreach($allParticipations as $stat) {
            if($stat->sport == "soccer" && $stat->result == 'victory'){
                array_push($finalStats['soccerVictory'], $stat);               
            }
            if($stat->sport == "basketball" && $stat->result == 'victory'){
                array_push($finalStats['basketballVictory'], $stat);               
            }
            if($stat->sport == "tennis" && $stat->result == 'victory'){
                array_push($finalStats['tennisVictory'], $stat);               
            }
            if($stat->sport == "soccer"){
                array_push($finalStats['soccer'], $stat);               
            }
            if($stat->sport == "basketball"){
                array_push($finalStats['basketball'], $stat);               
            }
            if($stat->sport == "tennis"){
                array_push($finalStats['tennis'], $stat);               
            }
        }

        if(count($finalStats['soccerVictory']) > 0 && count($finalStats['soccer']) > 0){
            $percentage['soccer'] = count($finalStats['soccerVictory']) / count($finalStats['soccer']);
        }
        if(count($finalStats['basketballVictory']) > 0 && count($finalStats['basketball']) > 0){
            $percentage['basketball'] = count($finalStats['basketballVictory']) / count($finalStats['basketball']);
        }
        if(count($finalStats['tennisVictory']) > 0 && count($finalStats['tennis']) > 0){
            $percentage['tennis'] = count($finalStats['tennisVictory']) / count($finalStats['tennis']);
        }

        return ResponseGenerator::generateResponse("OK", 200, [$percentage, $streak], "Usuario obtenido");
    }
}
