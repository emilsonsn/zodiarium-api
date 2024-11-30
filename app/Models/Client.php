<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    public $table = 'clients';

    protected $fillable = [
        'name',
        'gender',
        'address',
        'day_birth',
        'month_birth',
        'year_birth',
        'hour_birth',
        'minute_birth',
        'email',
        'ddi',
        'whatsapp',
        'status',
        'client_id'
    ];

    public function client(){
        return $this->belongsTo(Client::class, 'client_id');
    }
}
