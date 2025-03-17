<?php

namespace App\Models\SicalRsp;

use App\Models\Master\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dmo extends Model
{
    use HasFactory;

    protected $table = 'dmo';

    protected $fillable = [
        'date',
        'value',
        'remark'
    ];

    public function simulation()
    {
        return $this->hasMany(Simulation::class, 'id_dmo');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
