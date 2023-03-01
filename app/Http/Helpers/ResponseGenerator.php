<?php

namespace App\Http\Helpers;

class ResponseGenerator {

    //Response Generator
    public static function generateResponse($status,$code, $data, $msg=""){

        $response = [
            "status" => $status,
            "code" => $code
        ];

        if($msg) {
            $response['message'] = $msg;
        }
        if($data){
            $response['data'] = $data;
        }
        
        return json_encode($response);
    }

}