<?php

namespace App\Models\CashFlowSchedule;

use App\Models\Master\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriCashFlowSchedule extends Model
{
    use HasFactory;

    protected $table = 'kategori_cash_flow_schedule';

    protected $fillable = [
        'name',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function cashFlowSchedules()
    {
        return $this->hasMany(CashFlowSchedule::class, 'kategori_id');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
