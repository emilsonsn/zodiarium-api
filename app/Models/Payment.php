<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    public $table = 'payments';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    public $fillable = [
        'sale_id',
        'status',
        'identifier',
        'reference',
        'entity',
        'transaction_id',
        'value',
    ];

    public function sale(){
        return $this->belongsTo(Sale::class);
    }

}
