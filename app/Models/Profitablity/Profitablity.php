<?php

namespace App\Models\Profitablity;

use App\Models\Master\Log;
use App\Models\Master\Pmg;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profitablity extends Model
{
    use HasFactory;

    protected $table = 'profitablity';

    protected $fillable = ['kategori_id', 'pmg_id', 'tanggal', 'value'];

    public function kategori()
    {
        return $this->belongsTo(KategoriProfitablity::class, 'kategori_id');
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
