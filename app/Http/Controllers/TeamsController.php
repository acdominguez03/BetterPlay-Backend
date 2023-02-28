<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResponseGenerator;
use App\Models\Team;
use Illuminate\Support\Facades\Validator;

class TeamsController extends Controller
{
    public function addNbaTeams(Request $request){
        $json = $request->getContent();
        $data = json_decode($json);

        if($data){
            foreach ($data as $newTeam){
                $team = new Team();
                $team->name = $newTeam->name;
                $team->logo = $newTeam->logo;
                $team->sport = "basketball";
                $team->save();
            }
        }
    }
    public function addLaLigaTeams(Request $request){
        $json = $request->getContent();
        $data = json_decode($json);

        if($data){
            foreach ($data as $newTeam){
                $team = new Team();
                $team->name = $newTeam->name;
                $team->logo = $newTeam->logo;
                $team->sport = "soccer";
                $team->save();
            }
        }
    }
}
