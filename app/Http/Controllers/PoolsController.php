<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Helpers\ResponseGenerator;
use Illuminate\Support\Facades\Validator;
use App\Models\Pool;
use App\Models\User;
use App\Models\PoolEvent;
use App\Models\PoolParticipation;
use App\Models\Notification;
use App\Models\SpecialPoolEvent;
use Carbon\Carbon;
use App\Jobs\PoolCoinsDealer;
use Illuminate\Support\Facades\DB;


class PoolsController extends Controller
{
    /**
     * @OA\Put(
     *     path="/api/pools/create",
     *     tags={"pools"},
     *     summary="Crea una nueva quiniela",
     *     description="Recibe el nombre, la fecha final y los partidos de una quiniela y crea una nueva quiniela",
     *     @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                      type="object",
     *                      @OA\Property(
     *                          property="name",
     *                          type="string"
     *                      ),
     *                      @OA\Property(
     *                          property="matches",
     *                          type="string"
     *                      ),
     *                      @OA\Property(
     *                          property="finalDate",
     *                          type="integer"
     *                      ),
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Quiniela guardada correctamente"
     *     )
     * )
     */
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
                }catch(\Exception $e){
                    return ResponseGenerator::generateResponse("KO", 304, $e, ["Error al guardar"]);
                }

                for ($i = 0; $i < count($data->matches); $i++) {
                    if($i < 14) {
                        $newMatch = new PoolEvent();
                        $newMatch->home_id = $data->matches[$i]->homeId;
                        $newMatch->away_id = $data->matches[$i]->awayId;
                        $newMatch->date = $data->matches[$i]->date;
                        $newMatch->pool_id = $pool->id;

                        try {
                            $newMatch->save();
                        }catch(\Exception $e) {
                            $pool->delete();
                            return ResponseGenerator::generateResponse("KO", 304, $e, ["Error al guardar el evento"]);
                        }
                    }else{
                        $newEspecialMatch = new SpecialPoolEvent();
                        $newEspecialMatch->home_id = $data->matches[$i]->homeId;
                        $newEspecialMatch->away_id = $data->matches[$i]->awayId;
                        $newEspecialMatch->date = $data->matches[$i]->date;
                        $newEspecialMatch->pool_id = $pool->id;

                        try {
                            $newEspecialMatch->save();
                        }catch(\Exception $e) {
                            $pool->delete();
                            return ResponseGenerator::generateResponse("KO", 304, $e, ["Error al guardar el evento especial"]);
                        }
                    }
                }

                return ResponseGenerator::generateResponse("OK", 200, null, ["Quiniela guardada correctamente"]);

            }
        }else{
            return ResponseGenerator::generateResponse("KO", 500, null, ["Datos no registrados"]);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/pools/list",
     *     tags={"pools"},
     *     summary="Obtiene las quinielas disponibles",
     *     description="Obtiene el listado de todas las quinielas disponibles, es decir, cuya fecha final sea mayor a la actual",
     *     @OA\Response(
     *         response=200,
     *         description="Quinielas encontradas"
     *     )
     * )
     */
    public function list(){
        $currentDate = Carbon::now()->timestamp;
        try{
            $pools = Pool::where('finaldate', '>', $currentDate)->get();
            PoolCoinsDealer::dispatch();
            return ResponseGenerator::generateResponse("OK", 200, $pools, ["Quinielas encontradas"]);
        }catch(\Exception $e){
            return ResponseGenerator::generateResponse("KO", 304, $e, ["Error al buscar"]);
        }    
    }

    /**
     * @OA\Post(
     *     path="/api/pools/participateInPool",
     *     tags={"pools"},
     *     summary="Participación en una quiniela",
     *     description="Recibe la id de la quiniela en la que participa, las monedas y los resultados para esta quiniela y crea una participación y recibe una notificación el usuario en cuestión",
     *     @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                      type="object",
     *                      @OA\Property(
     *                          property="poolId",
     *                          type="integer"
     *                      ),
     *                      @OA\Property(
     *                          property="coins",
     *                          type="integer"
     *                      ),
     *                      @OA\Property(
     *                          property="poolResults",
     *                          type="string"
     *                      ),
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Participación creada"
     *     )
     * )
     */
    public function participateInPool(Request $request){
        $json = $request->getContent();

        $data = json_decode($json);

        if($data){
            $rules = array(
                'poolId' => 'required|integer|exists:pools,id',
                'coins' => 'required|integer',
                'poolResults' => 'required|array|max:15'
            );
        
            $customMessages = array(
                'poolId.required' => 'La id de la quiniela es necesaria',
                'poolId.integer' => 'La id de la quiniela debe ser numérica',
                'poolId.exists:events,id' => 'La id de la quiniela debe existir en la tabla de quinielas',
                'coins.required' => 'Es necesario saber el número de monedas a apostar',
                'coins.integer' => 'Es necesario que las monedas sean numéricas',
                'poolResults.required' => 'Es necesario añadir los resultados',
                'poolResults.array' => 'Los resultados de las quinielas debe ser un array',
                'poolResults.max:15' => 'El array tiene un máximo de 15 posiciones'
            );
            $validate = Validator::make(json_decode($json,true), $rules, $customMessages);

            if($validate->fails()){
                return ResponseGenerator::generateResponse("OK", 422, null, $validate->errors()->all());
            }else{
                
                //Obtener el usuario a través del token
                $user = auth()->user();

                $pool = Pool::find($data->poolId);
    
                $participations = DB::table('pool_participations')
                ->where('pool_id','=', $pool->id)
                ->where('user_id', '=', $user->id)
                ->get();

                if(empty($pool)){
                    return ResponseGenerator::generateResponse("KO", 422, null, ["Quiniela con esa id no encontrada"]);
                }else if(!count($participations) == 0){
                    return ResponseGenerator::generateResponse("KO", 422, null , ["Ya has participado en esta quiniela"]);
                }else if($user->coins < $data->coins){
                    return ResponseGenerator::generateResponse("KO", 422, null, ["No tienes las monedas suficientes para participar"]);
                }else{
                    try{
                        //Disminuir las monedas del usuario y crear la participación
                        $user->coins -= $data->coins;
                        $user->save();

                        try {
                            $newParticipation = new PoolParticipation();
                            $newParticipation->user_id = $user->id;
                            $newParticipation->pool_id = $data->poolId;
                            $newParticipation->coins = $data->coins;
                            $newParticipation->teams_selected = json_encode($data->poolResults);
                            $newParticipation->save();
                        }catch(\Exception $e) {
                            return ResponseGenerator::generateResponse("KO", 405, $e, ["Error al crear la participación"]);
                        }

                        //Una vez creada la participación y guardado el usuario se crea la notificación
                        try {
                            $notification = new Notification();
                            $notification->text = "Has participado en la quiniela: ".$pool->name;
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

    /**
     * @OA\Post(
     *     path="/api/pools/finishPool",
     *     tags={"pools"},
     *     summary="Finalizar una quiniela",
     *     description="Recibe la id de la quiniela a finalizar y los resultados, actualiza todas las tablas y por último, envía notificación a todos los usuario que hayan participado y les suma o no las monedas según sus aciertos.",
     *     @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                      type="object",
     *                      @OA\Property(
     *                          property="poolId",
     *                          type="integer"
     *                      ),
     *                      @OA\Property(
     *                          property="poolResults",
     *                          type="string"
     *                      ),
     *              )
     *          )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Participación creada"
     *     )
     * )
     */
    public function finishPool(Request $request){
        $json = $request->getContent();
        $data = json_decode($json);

        if($data) {

            $events = PoolEvent::where('pool_id', '=', $data->poolId)->get();
            $specialEvent = SpecialPoolEvent::where('pool_id', '=', $data->poolId)->get();

            for ($i = 0; $i < count($events); $i++) {
                $events[$i]->result = $data->poolResults[$i]->result; 
                $events[$i]->save();
            }

            $specialEvent[0]->home_result = $data->poolResults[14]->home_result;
            $specialEvent[0]->away_result = $data->poolResults[14]->away_result;
            $specialEvent[0]->save();

            PoolCoinsDealer::dispatch();

            return ResponseGenerator::generateResponse("OK", 200, null, ["Quiniela finalizada"]);
        }
    }
}
