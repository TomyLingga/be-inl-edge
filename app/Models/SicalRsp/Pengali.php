<?php

namespace App\Models\SicalRsp;

use App\Models\Master\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengali extends Model
{
    use HasFactory;

    protected $table = 'pengali';

    protected $fillable = [
        'name',
        'value'
    ];

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
