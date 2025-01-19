<?php

namespace App\Http\Controllers;

use App\Models\Harga\Harga;
use App\Models\Kurs\Kurs;
use Illuminate\Http\Request;

class HargaViewer extends Controller
{
    public function indexPeriodHarga($tanggalAwal, $tanggalAkhir, $idMataUang)
{
    $data = Harga::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
        ->with('product')
        ->get();

    $kurs = Kurs::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
        ->where('id_mata_uang', $idMataUang)
        ->with('mataUang')
        ->orderBy('tanggal', 'asc')
        ->get();

    if ($data->isEmpty() || $kurs->isEmpty()) {
        return null;
    }

    // Group data by product and get the latest entry for each product
    $latestHarga = $data->groupBy('id_product')->map(function ($group) {
        return $group->sortByDesc('tanggal')->first();
    })->values();

    // Map kurs data by tanggal
    $kursMap = $kurs->keyBy('tanggal');

    // Attach kurs and calculate foreign prices only for bulk products
    $latestHarga->each(function ($item) use ($kursMap) {
        $item->kurs = $kursMap->get($item->tanggal)->value ?? 1;
        if ($item->product->jenis === 'bulk') {
            $item->hargaAsingSpot = $item->kurs > 0 ? $item->spot / $item->kurs * 1000 : 0;
            $item->hargaAsingInventory = $item->kurs > 0 ? $item->inventory / $item->kurs * 1000 : 0;
        } else {
            $item->hargaBoxSpot = $item->product->konversi_pouch > 0 ? $item->product->konversi_pouch * $item->spot : 0;
            $item->hargaBoxInventory = $item->product->konversi_pouch > 0 ? $item->product->konversi_pouch * $item->inventory : 0;
            $item->hargaAsingSpot = $item->kurs > 0 ? $item->spot / $item->kurs : 0;
            $item->hargaAsingInventory = $item->kurs > 0 ? $item->inventory / $item->kurs : 0;
            $item->hargaAsingBoxSpot = $item->kurs > 0 ? $item->hargaBoxSpot / $item->kurs : 0;
            $item->hargaAsingBoxInventory = $item->kurs > 0 ? $item->hargaBoxInventory / $item->kurs : 0;
        }
    });

    // Separate bulk and ritel products and remove indices
    [$latestHargaBulk, $latestHargaRitel] = $latestHarga->partition(function ($item) {
        return $item->product->jenis === 'bulk';
    });

    return [
        'latestHargaBulk' => $latestHargaBulk->values(), // Remove indices
        'latestHargaRitel' => $latestHargaRitel->values(), // Remove indices
    ];
}




}
