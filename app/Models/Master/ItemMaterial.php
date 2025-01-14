<?php

namespace App\Models\Master;

use App\Models\LaporanMaterial\LaporanMaterial;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemMaterial extends Model
{
    use HasFactory;

    protected $table = 'item_material';

    protected $fillable = [
        'name',
        'jenis_laporan_id',
        'kategori',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function jenisLaporan()
    {
        return $this->belongsTo(JenisLaporanMaterial::class, 'jenis_laporan_id');
    }

    public function laporanMaterials()
    {
        return $this->hasMany(LaporanMaterial::class, 'item_material_id');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
