<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisLaporanProduksi extends Model
{
    use HasFactory;

    protected $table = 'jenis_laporan_produksi';

    protected $fillable = [
        'name',
        'condition_olah', //'sum', 'use_higher', 'use_lower', 'difference'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function itemProduksi()
    {
        return $this->hasMany(ItemProduksi::class, 'jenis_laporan_id');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
