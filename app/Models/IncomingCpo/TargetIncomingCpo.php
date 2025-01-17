<?php

namespace App\Models\IncomingCpo;

use App\Models\Master\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TargetIncomingCpo extends Model
{
    use HasFactory;

    protected $table = 'target_incoming_cpo';

    protected $fillable = [
        'tanggal',
        'qty',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
