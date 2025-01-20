<?php

namespace App\Models\Packaging;

use App\Models\Master\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisLaporanPackaging extends Model
{
    use HasFactory;

    protected $table = 'jenis_laporan_packaging';

    protected $fillable = [
        'name',
        'condition_olah',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function itemPackaging()
    {
        return $this->hasMany(ItemPackaging::class, 'jenis_laporan_id');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
