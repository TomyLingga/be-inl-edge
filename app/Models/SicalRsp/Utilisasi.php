<?php

namespace App\Models\SicalRsp;

use App\Models\Master\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Utilisasi extends Model
{
    use HasFactory;

    protected $table = 'utilisasi';

    protected $fillable = [
        'name',
        'value'
    ];

    public function cost()
    {
        return $this->hasMany(Cost::class, 'id_simulation');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
