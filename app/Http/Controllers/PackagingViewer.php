<?php

namespace App\Http\Controllers;

use App\Models\Packaging\LaporanPackaging;
use App\Models\Packaging\TargetPackaging;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PackagingViewer extends Controller
{
    public function indexPeriodTargetPackaging($tanggalAwal, $tanggalAkhir, $idPackaging)
    {
        // Fetch the target data
        $data = TargetPackaging::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
            ->where('packaging_id', $idPackaging)
            ->with('uraian', 'packaging', 'jenis')
            ->get();

        if ($data->isEmpty()) {
            return null;
        }

        // Get the laporan data for final value comparison
        $dataLaporan = $this->indexPeriodLaporanPackaging($tanggalAwal, $tanggalAkhir, $idPackaging)['summary'];

        // Group the data by 'jenis' (packaging type)
        $groupedData = $data->groupBy('jenis.id')
            ->map(function ($jenisGroup) use ($dataLaporan) {
                // Get the 'jenis' name and id
                $jenisName = $jenisGroup->first()->jenis->name;
                $jenisId = $jenisGroup->first()->jenis->id;

                // Get the corresponding 'produk_hasil' data from the laporan
                $produkHasilData = $dataLaporan->first(function ($item) use ($jenisName) {
                    return $item['jenis_laporan'] === $jenisName;
                });

                // Extract final value for 'produk_hasil'
                $finalValueProdukHasil = $produkHasilData['kategori_data']
                    ->firstWhere('kategori', 'produk_hasil')['finalValue'] ?? 0;

                // Calculate summary for each target
                $targetSummary = $jenisGroup->groupBy('uraian.id')
                    ->map(function ($uraianGroup) use ($finalValueProdukHasil) {
                        $uraianName = $uraianGroup->first()->uraian->nama;
                        $targetValue = $uraianGroup->sum('value'); // Sum values for the uraian group

                        // Calculate percentage
                        $percentage = ($targetValue != 0) ? ($finalValueProdukHasil / $targetValue) * 100 : 0;

                        return [
                            'nama' => $uraianName,
                            'value' => $targetValue,
                            'percentage' => $percentage
                        ];
                    })
                    ->values(); // Re-index the uraian group

                return [
                    'idJenis' => $jenisId,
                    'name' => $jenisName,
                    'finalValueProdukHasil' => $finalValueProdukHasil,
                    'summary' => $targetSummary
                ];
            })
            ->values(); // Re-index the jenis group

        return $groupedData;
    }

    public function indexPeriodLaporanPackaging($tanggalAwal, $tanggalAkhir, $idPackaging)
    {
        $data = LaporanPackaging::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
            ->where('packaging_id', $idPackaging)
            ->with([
                'itemPackaging.jenisLaporan',
                'itemPackaging.productHasil',
                'packaging'
            ])
            ->get();

        $periodData = $data;

        if ($data->isEmpty()) {
            return null;
        }

        $groupedData = $data->groupBy('itemPackaging.jenisLaporan.name')
            ->map(function ($itemsByJenis) {
                return [
                    'jenis_laporan' => $itemsByJenis->first()->itemPackaging->jenisLaporan->name,
                    'kategori_data' => $itemsByJenis->groupBy('itemPackaging.kategori')
                        ->map(function ($itemsByKategori) {
                            return [
                                'kategori' => $itemsByKategori->first()->itemPackaging->kategori,
                                'finalValue' => $itemsByKategori->sum('qty'),
                                'items' => $itemsByKategori->groupBy('itemPackaging.name')
                                    ->map(function ($itemsByName) {
                                        $totalQty = $itemsByName->sum('qty');
                                        $item = $itemsByName->first()->itemPackaging;

                                        // Initialize result
                                        $result = [
                                            'name' => $item->name,
                                            'totalQty' => $totalQty,
                                            'detail' => $itemsByName->map(function ($item) {
                                                return [
                                                    'id' => $item->id,
                                                    'item_packaging_id' => $item->item_packaging_id,
                                                    'tanggal' => $item->tanggal,
                                                    'packaging_id' => $item->packaging_id,
                                                    'qty' => $item->qty,
                                                    'created_at' => $item->created_at,
                                                    'updated_at' => $item->updated_at,
                                                    'item_packaging' => [
                                                        'id' => $item->itemPackaging->id,
                                                        'name' => $item->itemPackaging->name,
                                                        'jenis_laporan_id' => $item->itemPackaging->jenis_laporan_id,
                                                        'kategori' => $item->itemPackaging->kategori,
                                                        'product_id' => $item->itemPackaging->product_id,
                                                        'jenis_laporan' => [
                                                            'id' => $item->itemPackaging->jenisLaporan->id,
                                                            'name' => $item->itemPackaging->jenisLaporan->name,
                                                            'condition_olah' => $item->itemPackaging->jenisLaporan->condition_olah
                                                        ],
                                                        'product_hasil' => $item->itemPackaging->productHasil
                                                    ],
                                                    'packaging' => $item->packaging
                                                ];
                                            })
                                        ];

                                        if ($item->kategori == 'produk_hasil' && $item->productHasil) {
                                            $productHasil = $item->productHasil;
                                            $result['pouch'] = $totalQty * $productHasil->konversi_pouch;
                                            $result['ton'] = $totalQty * $productHasil->konversi_ton;
                                        }

                                        return $result;
                                    })->values()
                            ];
                        })->values()
                ];
            })->values();

        return [
            "summary" => $groupedData,
            "dataPeriod" => $periodData,
        ];
    }
}
