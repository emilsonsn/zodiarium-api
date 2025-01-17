<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Genereated extends Model
{
    use HasFactory;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $table = 'generateds';

    public $fillable = [
        'client_id',
        'path'
    ];

    public function client(){
        return $this->belongsTo(Client::class);
    }
}
