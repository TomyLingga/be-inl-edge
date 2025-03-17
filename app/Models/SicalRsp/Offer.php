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
        'volume'
    ];

    public function simulation()
    {
        return $this->hasOne(Simulation::class, 'id_offer');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
