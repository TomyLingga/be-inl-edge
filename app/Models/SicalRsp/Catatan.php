<?php

namespace App\Models\SicalRsp;

use App\Models\Master\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Catatan extends Model
{
    use HasFactory;

    protected $table = 'catatan';

    protected $fillable = [
        'id_simulation',
        'judul'
    ];

    public function simulation()
    {
        return $this->belongsTo(Simulation::class, 'id_simulation');
    }

    public function detailCatatan()
    {
        return $this->hasMany(DetailCatatan::class, 'id_catatan');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
