<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Friend extends Model
{
    use HasFactory;

    public function requestUser(){
        return $this->belongsTo(User::class,'request_id');
    }
    public function receiveUser(){
        return $this->belongsTo(User::class,'receive_id');
    }
}
