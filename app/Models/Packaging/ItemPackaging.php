<?php

namespace App\Models\Packaging;

use App\Models\Master\Log;
use App\Models\Master\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemPackaging extends Model
{
    use HasFactory;

    protected $table = 'item_packaging';

    protected $fillable = [
        'product_id',
        'name',
        'jenis_laporan_id',
        'kategori',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function jenisLaporan()
    {
        return $this->belongsTo(JenisLaporanPackaging::class, 'jenis_laporan_id');
    }

    public function productHasil()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function laporanPackaging()
    {
        return $this->hasMany(LaporanPackaging::class, 'item_packaging_id');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
