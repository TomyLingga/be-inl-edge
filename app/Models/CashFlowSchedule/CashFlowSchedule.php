<?php

namespace App\Models\CashFlowSchedule;

use App\Models\Master\Log;
use App\Models\Master\Pmg;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashFlowSchedule extends Model
{
    use HasFactory;

    protected $table = 'cash_flow_schedule';

    protected $fillable = [
        'kategori_id',
        'pmg_id',
        'name',
        'tanggal',
        'value',
        'pay_status_id',
    ];

    public function kategori()
    {
        return $this->belongsTo(KategoriCashFlowSchedule::class, 'kategori_id');
    }

    public function pmg()
    {
        return $this->belongsTo(Pmg::class, 'pmg_id');
    }

    public function payStatus()
    {
        return $this->belongsTo(PayStatusCashFlowSchedule::class, 'pay_status_id');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
