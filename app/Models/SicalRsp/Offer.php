<?php

namespace App\Models\SicalRsp;

use App\Models\Master\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;

    protected $table = 'offer';

    protected $fillable = [
        'buyer_name',
        'price',
        'volume',
        'simulation_id'
    ];

    public function simulation()
    {
        return $this->belongsTo(Simulation::class, 'simulation_id');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
