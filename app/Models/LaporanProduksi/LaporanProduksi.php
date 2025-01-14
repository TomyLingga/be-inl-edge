<?php

namespace App\Models\LaporanProduksi;

use App\Models\Master\ItemProduksi;
use App\Models\Master\Log;
use App\Models\Master\Pmg;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaporanProduksi extends Model
{
    use HasFactory;

    protected $table = 'laporan_produksi';

    protected $fillable = [
        'item_produksi_id',
        'tanggal',
        'pmg_id',
        'qty',
    ];

    public function itemProduksi()
    {
        return $this->belongsTo(ItemProduksi::class, 'item_produksi_id');
    }

    public function pmg()
    {
        return $this->belongsTo(Pmg::class, 'pmg_id');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
