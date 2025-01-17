<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Genereted extends Model
{
    use HasFactory;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $table = 'generateds';

    public $fillable = [
        'client_id',
        'path'
    ];

    public function getPathAttribute($value){
        return $value ?  asset($value) : null;
    }

    public function client(){
        return $this->belongsTo(Client::class);
    }
}
