<?php

namespace App\Models\CashFlowMovement;

use App\Models\Master\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriCashFlowMovement extends Model
{
    use HasFactory;

    protected $table = 'kategori_cash_flow_movement';

    protected $fillable = [
        'name',
        'nilai',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function cashFlowMovements()
    {
        return $this->hasMany(CashFlowMovement::class, 'kategori_id');
    }

    /**
     * Polymorphic relationship with logs.
     */
    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
