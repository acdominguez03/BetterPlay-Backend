<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Friend;
use App\Http\Helpers\ResponseGenerator;

/**
 * @OA\Info(
 *      version="1.0.0", 
 *      title="Controlador de Peticiones de Amistad",
 *      description="Aquí está alojada toda la lógica de los amigos",
 * )
 */
class FriendsController extends Controller
{
    /**
     * @OA\Put(
     *     path="/api/friends/friendRequest",
     *     summary="Crea una petición de amistad",
     *     description="Recibe la id de otro user y le manda una petición de amistad",
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
     *         description="Actualizado Correctamente"
     *     ),
     * )
     */
    public function friendRequest(Request $request){
        $json = $request->getContent();
        $data = json_decode($json);
        
        if($data){
            $rules = array(
                'id' => 'required|exists:users,id'
            );
            $customMessages = array(
                'id.required' => 'El Id del Usuario es necesario',
                'id.exists:users,id' => 'El Id de Usuario tiene que existir en la tabla de Users'
            );
            $validate = Validator::make(json_decode($json, true),$rules, $customMessages);
    
            if($validate->fails()){
                return ResponseGenerator::generateResponse("KO", 422, null, $validate->errors()->all());
            }

            $userLoged = auth()->user();
            $friend = new Friend();
            $friend->request_id = $userLoged->id;
            $friend->receive_id = $data->id;
            try{
                $friend->save();
                return ResponseGenerator::generateResponse("OK", 200,  $friend , ["Petición de amistad realizada correctamente."]);
            }catch(\Exception $e){
                return ResponseGenerator::generateResponse("KO", 404, null, ["No se han podido realizar la petición de amistad"]);
            } 
        }else{
            return ResponseGenerator::generateResponse("KO", 500, null, ["Datos no registrados"]);
        }

    }
}
