<?php

namespace App\Models\Packaging;

use App\Models\Master\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PgSql\Lob;

class TargetPackaging extends Model
{
    use HasFactory;

    protected $table = 'target_packaging';

    protected $fillable = [
        'uraian_id',
        'packaging_id',
        'jenis_id',
        'tanggal',
        'value',
    ];

    public function uraian()
    {
        return $this->belongsTo(TargetPackagingUraian::class, 'uraian_id');
    }

    public function packaging()
    {
        return $this->belongsTo(Packaging::class, 'packaging_id');
    }

    public function jenis()
    {
        return $this->belongsTo(JenisLaporanPackaging::class, 'jenis_id');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
