<?php

namespace App\Http\Controllers;

use App\Models\LaporanProduksi\LaporanProduksi;
use Illuminate\Http\Request;

class LaporanProduksiViewer extends Controller
{
    public function indexPeriodLaporanProd($tanggalAwal, $tanggalAkhir, $idPmg)
    {
        $data = LaporanProduksi::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
            ->where('pmg_id', $idPmg)
            ->with(['itemProduksi.jenisLaporan', 'pmg'])
            ->get();

        if ($data->isEmpty()) {
            return null;
        }

        $groupedData = $data->groupBy(function ($laporanProduksi) {
            return $laporanProduksi->itemProduksi->jenisLaporan->name ?? 'Unknown';
        });

        $result = $groupedData->map(function ($laporanGroup, $jenisLaporanName) {
            $kategoriGrouped = $laporanGroup->groupBy(function ($laporan) {
                return $laporan->itemProduksi->kategori ?? 'Unknown';
            });

            $finalValues = [
                'bahan_olah' => 0,
                'produk_hasil' => 0,
            ];

            $kategoriData = $kategoriGrouped->map(function ($kategoriGroup, $kategoriName) use (&$finalValues) {
                $items = $kategoriGroup->groupBy(function ($laporan) {
                    return $laporan->itemProduksi->name;
                })->map(function ($itemGroup, $itemName) {
                    $totalQty = $itemGroup->sum('qty');

                    return [
                        'name' => $itemName,
                        'totalQty' => $totalQty,
                        'detail' => $itemGroup->map(function ($laporan) {
                            return [
                                'id' => $laporan->id,
                                'item_produksi_id' => $laporan->item_produksi_id,
                                'tanggal' => $laporan->tanggal,
                                'pmg_id' => $laporan->pmg_id,
                                'qty' => $laporan->qty,
                                'created_at' => $laporan->created_at,
                                'updated_at' => $laporan->updated_at,
                                'item_produksi' => [
                                    'id' => $laporan->itemProduksi->id,
                                    'name' => $laporan->itemProduksi->name,
                                    'kategori' => $laporan->itemProduksi->kategori,
                                ],
                                'pmg' => [
                                    'id' => $laporan->pmg->id,
                                    'nama' => $laporan->pmg->nama,
                                    'lokasi' => $laporan->pmg->lokasi,
                                ],
                            ];
                        }),
                    ];
                })->values();

                if ($kategoriName === 'bahan_olah') {
                    $finalValues['bahan_olah'] = $items->sum('totalQty');
                } elseif ($kategoriName === 'produk_hasil') {
                    $finalValues['produk_hasil'] = $items->sum('totalQty');

                    $bahanOlahValue = $finalValues['bahan_olah'];
                    $items = $items->map(function ($item) use ($bahanOlahValue) {
                        $item['yieldPercentage'] = $bahanOlahValue > 0
                            ? number_format(($item['totalQty'] / $bahanOlahValue) * 100, 2)
                            : 0;
                        return $item;
                    });
                }

                return [
                    'kategori' => $kategoriName,
                    'finalValue' => $kategoriName === 'produk_hasil' || $kategoriName === 'bahan_olah' ? $finalValues[$kategoriName] : null,
                    'items' => $items,
                ];
            });

            $losses = $finalValues['bahan_olah'] - $finalValues['produk_hasil'];
            $lossesPercentage = $finalValues['bahan_olah'] > 0
                ? number_format(($losses / $finalValues['bahan_olah']) * 100, 2)
                : 0;

            return [
                'jenis_laporan' => $jenisLaporanName,
                'losses' => $losses,
                'lossesPercentage' => $lossesPercentage,
                'kategori_data' => $kategoriData->values(),
            ];
        })->values();

        return $result;
    }

}
