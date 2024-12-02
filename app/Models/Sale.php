<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    public $table = 'sales';

    public $fillable = [
        'client_id',
        'status',
    ];

    public function client(){
        return $this->belongsTo(Client::class);
    }


    public function products(){
        return $this->hasMany(SaleProduct::class);
    }
}
