<?php

namespace App\Models\CashFlowSchedule;

use App\Models\Master\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayStatusCashFlowSchedule extends Model
{
    use HasFactory;

    protected $table = 'pay_status_cash_flow_schedule';

    protected $fillable = [
        'name',
        'state',
        'remark',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function cashFlowSchedules()
    {
        return $this->hasMany(CashFlowSchedule::class, 'pay_status_id');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
