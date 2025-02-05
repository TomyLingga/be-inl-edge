<?php

namespace App\Http\Controllers;

use App\Models\Harga\Harga;
use App\Models\Harga\HargaSpot;
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

        if ($kurs->isEmpty()) {
            $kurs = collect([(object) ['tanggal' => null, 'value' => 0]]);
        }

        if ($data->isEmpty()) {
            return null;
        }

        // Map kurs data by tanggal
        $kursMap = $kurs->keyBy('tanggal');

        // Calculate prices for each item
        $data->each(function ($item) use ($kursMap) {
            $kursValue = $kursMap->get($item->tanggal)->value ?? 0;
            $item->kurs = $kursValue > 0 ? $kursValue : 0;

            if ($item->product->jenis === 'bulk') {
                $item->hargaAsingInventory = $kursValue > 0 ? round(($item->inventory / $kursValue) * 1000, 2) : 0;
            } else {
                $item->hargaBoxInventory = $item->product->konversi_pouch > 0 ? round($item->product->konversi_pouch * $item->inventory, 2) : 0;
                $item->hargaAsingInventory = $kursValue > 0 ? round($item->inventory / $kursValue, 2) : 0;
                $item->hargaAsingBoxInventory = $kursValue > 0 ? round($item->hargaBoxInventory / $kursValue, 2) : 0;
            }
        });

        // Separate bulk and ritel products
        [$bulkData, $ritelData] = $data->partition(fn($item) => $item->product->jenis === 'bulk');

        // Get the latest price per product
        $latestHarga = $data->groupBy('id_product')->map(fn($group) => $group->sortByDesc('tanggal')->first())->values();

        // Separate latest bulk and ritel prices
        [$latestHargaBulk, $latestHargaRitel] = $latestHarga->partition(fn($item) => $item->product->jenis === 'bulk');

        // Format period data into the required structure
        $formatGroupedData = function ($collection) {
            return $collection
                ->groupBy(fn($item) => $item->product->name) // Group by product name
                ->map(fn($items, $name) => [
                    'name' => $name,
                    'details' => $items->values(),
                ])
                ->values(); // Convert to indexed array
        };

        return [
            'latestHargaBulk' => $latestHargaBulk->values(),
            'latestHargaRitel' => $latestHargaRitel->values(),
            'periodHargaBulk' => ['products' => $formatGroupedData($bulkData)],
            'periodHargaRitel' => ['products' => $formatGroupedData($ritelData)],
        ];
    }

    public function indexPeriodHargaSpot($tanggalAwal, $tanggalAkhir, $idMataUang)
    {
        $data = HargaSpot::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
            ->with('product')
            ->get();

        $kurs = Kurs::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
            ->where('id_mata_uang', $idMataUang)
            ->with('mataUang')
            ->orderBy('tanggal', 'asc')
            ->get();

        if ($kurs->isEmpty()) {
            $kurs = collect([(object) ['tanggal' => null, 'value' => 0]]);
        }

        if ($data->isEmpty()) {
            return null;
        }

        // Map kurs data by tanggal
        $kursMap = $kurs->keyBy('tanggal');

        // Calculate prices for each item
        $data->each(function ($item) use ($kursMap) {
            $kursValue = $kursMap->get($item->tanggal)->value ?? 0;
            $item->kurs = $kursValue > 0 ? $kursValue : 0;

            if ($item->product->jenis === 'bulk') {
                $item->hargaAsingSpot = $kursValue > 0 ? round(($item->spot / $kursValue) * 1000, 2) : 0;
            } else {
                $item->hargaBoxSpot = $item->product->konversi_pouch > 0 ? round($item->product->konversi_pouch * $item->spot, 2) : 0;
                $item->hargaAsingSpot = $kursValue > 0 ? round($item->spot / $kursValue, 2) : 0;
                $item->hargaAsingBoxSpot = $kursValue > 0 ? round($item->hargaBoxSpot / $kursValue, 2) : 0;
            }
        });

        // Separate bulk and ritel products
        [$bulkData, $ritelData] = $data->partition(fn($item) => $item->product->jenis === 'bulk');

        // Get the latest price per product
        $latestHarga = $data->groupBy('id_product')->map(fn($group) => $group->sortByDesc('tanggal')->first())->values();

        // Separate latest bulk and ritel prices
        [$latestHargaBulk, $latestHargaRitel] = $latestHarga->partition(fn($item) => $item->product->jenis === 'bulk');

        // Format period data into the required structure
        $formatGroupedData = function ($collection) {
            return $collection
                ->groupBy(fn($item) => $item->product->name) // Group by product name
                ->map(fn($items, $name) => [
                    'name' => $name,
                    'details' => $items->values(),
                ])
                ->values(); // Convert to indexed array
        };

        return [
            'latestHargaBulk' => $latestHargaBulk->values(),
            'latestHargaRitel' => $latestHargaRitel->values(),
            'periodHargaBulk' => ['products' => $formatGroupedData($bulkData)],
            'periodHargaRitel' => ['products' => $formatGroupedData($ritelData)],
        ];
    }

}
