<?php

namespace App\Models\SicalRsp;

use App\Models\Master\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cost extends Model
{
    use HasFactory;

    protected $table = 'costs';

    protected $fillable = [
        'id_master_cost',
        'value',
        'id_utilisasi',
        'id_simulation'
    ];

    public function simulation()
    {
        return $this->belongsTo(Simulation::class, 'id_simulation');
    }

    public function masterCost()
    {
        return $this->belongsTo(MasterCost::class, 'id_master_cost');
    }

    public function utilisasi()
    {
        return $this->belongsTo(Utilisasi::class, 'id_utilisasi');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
