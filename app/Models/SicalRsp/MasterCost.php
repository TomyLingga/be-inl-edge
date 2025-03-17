<?php

namespace App\Models\SicalRsp;

use App\Models\Master\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterCost extends Model
{
    use HasFactory;

    protected $table = 'master_costs';

    protected $fillable = [
        'name',
        'contribute_to_margin'
    ];

    public function cost()
    {
        return $this->hasMany(Cost::class, 'id_master_cost');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
