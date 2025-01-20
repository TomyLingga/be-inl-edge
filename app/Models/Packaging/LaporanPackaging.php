<?php

namespace App\Models\Packaging;

use App\Models\Master\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaporanPackaging extends Model
{
    use HasFactory;

    protected $table = 'laporan_packaging';

    protected $fillable = [
        'item_packaging_id',
        'packaging_id',
        'tanggal',
        'qty',
    ];

    public function itemPackaging()
    {
        return $this->belongsTo(ItemPackaging::class, 'item_packaging_id');
    }

    public function packaging()
    {
        return $this->belongsTo(Packaging::class, 'packaging_id');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
