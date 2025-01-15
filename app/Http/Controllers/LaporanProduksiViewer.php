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

            $finalValueBahanOlah = 0;
            $finalValueProdukHasil = 0;

            $kategoriData = $kategoriGrouped->map(function ($kategoriGroup, $kategoriName) use (&$finalValueBahanOlah, &$finalValueProdukHasil) {
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
                    $conditionOlah = $kategoriGroup->first()->itemProduksi->jenisLaporan->condition_olah ?? 'sum';

                    if (count($items) > 1) {
                        $values = $items->pluck('totalQty')->all();
                        switch ($conditionOlah) {
                            case 'sum':
                                $finalValueBahanOlah = array_sum($values);
                                break;
                            case 'use_higher':
                                $finalValueBahanOlah = max($values);
                                break;
                            case 'use_lower':
                                $finalValueBahanOlah = min($values);
                                break;
                            case 'difference':
                                $finalValueBahanOlah = abs($values[0] - $values[1]); // Assuming only two items for difference
                                break;
                            default:
                                $finalValueBahanOlah = null;
                                break;
                        }
                    } else {
                        $finalValueBahanOlah = $items->first()['totalQty'];
                    }

                    return [
                        'kategori' => $kategoriName,
                        'condition_olah' => $conditionOlah,
                        'finalValue' => $finalValueBahanOlah,
                        'items' => $items,
                    ];
                }

                if ($kategoriName === 'produk_hasil') {
                    $finalValueProdukHasil = $items->sum('totalQty');
                    $items = $items->map(function ($item) use ($finalValueBahanOlah) {
                        $item['yieldPercentage'] = $finalValueBahanOlah > 0 ? ($item['totalQty'] / $finalValueBahanOlah) * 100 : 0;
                        return $item;
                    });

                    return [
                        'kategori' => $kategoriName,
                        'finalValue' => $finalValueProdukHasil,
                        'items' => $items,
                    ];
                }

                if ($kategoriName === 'others') {
                    $finalValueProdukHasil = $items->sum('totalQty');

                    return [
                        'kategori' => $kategoriName,
                        'finalValue' => $finalValueProdukHasil,
                        'items' => $items,
                    ];
                }

                return [
                    'kategori' => $kategoriName,
                    'items' => $items,
                ];
            });

            $losses = $finalValueBahanOlah - $finalValueProdukHasil;
            $lossesPercentage = $finalValueBahanOlah > 0 ? ($losses / $finalValueBahanOlah) * 100 : 0;

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
