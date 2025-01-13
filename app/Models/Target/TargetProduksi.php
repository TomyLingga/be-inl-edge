<?php

namespace App\Models\Target;

use App\Models\Master\Log;
use App\Models\Master\Pmg;
use App\Models\Master\TargetProduksiUraian;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class TargetProduksi extends Model
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'id';

    protected $table = 'target_produksi';

    protected $fillable = [
        'uraian_id',
        'pmg_id',
        'tanggal',
        'value'
    ];

    public function uraian()
    {
        return $this->belongsTo(TargetProduksiUraian::class, 'uraian_id');
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
