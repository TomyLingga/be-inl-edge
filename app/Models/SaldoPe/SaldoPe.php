<?php

namespace App\Models\SaldoPe;

use App\Models\Master\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaldoPe extends Model
{
    use HasFactory;

    protected $table = 'saldo_pe';

    protected $fillable = [
        'tanggal',
        'saldo_awal',
        'saldo_pakai',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    /**
     * Define the polymorphic relationship with logs.
     */
    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
