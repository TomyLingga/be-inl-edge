<?php

namespace App\Models\Kurs;

use App\Models\Master\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kurs extends Model
{
    use HasFactory;

    protected $table = 'kurs';

    protected $fillable = [
        'id_mata_uang',
        'tanggal',
        'value',
    ];

    public function mataUang()
    {
        return $this->belongsTo(MataUang::class, 'id_mata_uang');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
