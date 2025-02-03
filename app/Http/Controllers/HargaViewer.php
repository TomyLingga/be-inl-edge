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

        // Set default kurs value if empty
        if ($kurs->isEmpty()) {
            $kurs = collect([(object) ['tanggal' => null, 'value' => 0]]);
        }

        if ($data->isEmpty()) {
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
            $kursValue = $kursMap->get($item->tanggal)->value ?? 0;
            $item->kurs = $kursValue > 0 ? $kursValue : 1; // Default to 1 if kurs is 0

            if ($item->product->jenis === 'bulk') {
                $item->hargaAsingSpot = $kursValue > 0 ? round(($item->spot / $kursValue) * 1000, 2) : 0;
                $item->hargaAsingInventory = $kursValue > 0 ? round(($item->inventory / $kursValue) * 1000, 2) : 0;
            } else {
                $item->hargaBoxSpot = $item->product->konversi_pouch > 0 ? round($item->product->konversi_pouch * $item->spot, 2) : 0;
                $item->hargaBoxInventory = $item->product->konversi_pouch > 0 ? round($item->product->konversi_pouch * $item->inventory, 2) : 0;
                $item->hargaAsingSpot = $kursValue > 0 ? round($item->spot / $kursValue, 2) : 0;
                $item->hargaAsingInventory = $kursValue > 0 ? round($item->inventory / $kursValue, 2) : 0;
                $item->hargaAsingBoxSpot = $kursValue > 0 ? round($item->hargaBoxSpot / $kursValue, 2) : 0;
                $item->hargaAsingBoxInventory = $kursValue > 0 ? round($item->hargaBoxInventory / $kursValue, 2) : 0;
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
