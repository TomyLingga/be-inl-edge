<?php

namespace App\Models\Sdm;

use App\Models\Master\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sdm extends Model
{
    use HasFactory;
    protected $primaryKey = 'id';

    protected $table = 'sdm';

    protected $fillable = [
        'uraian_id',
        'nilai',
        'tanggal'
    ];

    public function uraian()
    {
        return $this->belongsTo(UraianSdm::class, 'uraian_id');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
