<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    public $table = 'products';

    public $fillable = [
        'title',
        'image',
        'amount',
        'is_active',
        'reports_array',
    ];

    public function sales(){
        return $this->hasMany(Sale::class);
    }
}