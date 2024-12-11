<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    public $table = 'settings';

    public $fillable = [
        'company_name',
        'company_url',
        'company_email',
        'company_phone',
        'company_bio',
        'theme',
        'logo',
        'footer_text',
        'api_key',
        'bearer_token',
        'tags'
    ];

    public function getLogoAttribute($value){
        return isset($value) ? asset("storage/settings/$value") : '';
    }
}
