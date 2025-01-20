<?php

namespace App\Models\Penjualan;

use App\Models\Master\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TargetPenjualanUraian extends Model
{
    use HasFactory;

    protected $table = 'target_penjualan_uraian';

    protected $fillable = ['nama'];

    /**
     * Polymorphic relation to logs.
     */
    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
