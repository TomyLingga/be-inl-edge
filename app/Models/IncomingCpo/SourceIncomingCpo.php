<?php

namespace App\Models\IncomingCpo;

use App\Models\Master\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SourceIncomingCpo extends Model
{
    use HasFactory;

    protected $table = 'source_incoming_cpo';

    protected $fillable = [
        'name',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function incomingCpo()
    {
        return $this->hasMany(IncomingCpo::class, 'source_id');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
