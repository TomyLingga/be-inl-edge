<?php

namespace App\Models\IncomingCpo;

use App\Models\Master\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomingCpo extends Model
{
    use HasFactory;

    protected $table = 'incoming_cpo';

    protected $fillable = [
        'tanggal',
        'qty',
        'harga',
        'source_id',
    ];

    public function source()
    {
        return $this->belongsTo(SourceIncomingCpo::class, 'source_id');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
