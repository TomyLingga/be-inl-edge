<?php

namespace App\Models\CashFlowMovement;

use App\Models\Master\Log;
use App\Models\Master\Pmg;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashFlowMovement extends Model
{
    use HasFactory;

    protected $table = 'cash_flow_movement';

    protected $fillable = [
        'kategori_id',
        'pmg_id',
        'tanggal',
        'value',
    ];

    /**
     * Relationship with KategoriCashFlowMovement.
     */
    public function kategori()
    {
        return $this->belongsTo(KategoriCashFlowMovement::class, 'kategori_id');
    }

    public function pmg()
    {
        return $this->belongsTo(Pmg::class, 'pmg_id');
    }

    /**
     * Polymorphic relationship with logs.
     */
    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
