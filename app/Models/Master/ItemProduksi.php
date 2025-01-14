<?php

namespace App\Models\Master;

use App\Models\LaporanProduksi\LaporanProduksi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemProduksi extends Model
{
    use HasFactory;

    protected $table = 'item_produksi';

    protected $fillable = [
        'name',
        'jenis_laporan_id', //'bahan_olah', 'produk_hasil', 'others'
        'kategori',
    ];

    public function jenisLaporan()
    {
        return $this->belongsTo(JenisLaporanProduksi::class, 'jenis_laporan_id');
    }

    public function laporanProduksi()
    {
        return $this->hasMany(LaporanProduksi::class, 'item_produksi_id');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
