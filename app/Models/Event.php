<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    public function homeTeam(){
        return $this->belongsTo(Team::class,'home_id');
    }
    public function awayTeam(){
        return $this->belongsTo(Team::class,'away_id');
    }

    public function users(){
        return $this->belongsToMany(User::class);
    }
}
