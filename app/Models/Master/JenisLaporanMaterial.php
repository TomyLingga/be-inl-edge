<?php

namespace App\Models\Master;

use App\Models\LaporanMaterial\NormaMaterial;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisLaporanMaterial extends Model
{
    use HasFactory;

    protected $table = 'jenis_laporan_material';

    protected $fillable = [
        'name',
        'condition_olah',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function items()
    {
        return $this->hasMany(ItemMaterial::class, 'jenis_laporan_id');
    }

    public function normaMaterials()
    {
        return $this->hasMany(NormaMaterial::class, 'jenis_laporan_id');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
