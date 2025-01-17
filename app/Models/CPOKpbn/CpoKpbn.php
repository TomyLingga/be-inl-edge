<?php

namespace App\Models\CPOKpbn;

use App\Models\Master\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CpoKpbn extends Model
{
    use HasFactory;

    protected $table = 'cpo_kpbn';

    protected $fillable = [
        'tanggal',
        'value',
    ];

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
