<?php

namespace App\Models\LaporanMaterial;

use App\Models\Master\ItemMaterial;
use App\Models\Master\Log;
use App\Models\Master\Pmg;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaporanMaterial extends Model
{
    use HasFactory;

    protected $table = 'laporan_material';

    protected $fillable = [
        'item_material_id',
        'tanggal',
        'pmg_id',
        'qty',
    ];

    public function itemMaterial()
    {
        return $this->belongsTo(ItemMaterial::class, 'item_material_id');
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
