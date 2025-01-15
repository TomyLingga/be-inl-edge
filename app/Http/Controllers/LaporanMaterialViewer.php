<?php

namespace App\Http\Controllers;

use App\Models\LaporanMaterial\LaporanMaterial;
use App\Models\LaporanMaterial\NormaMaterial;
use App\Models\LaporanProduksi\LaporanProduksi;
use Illuminate\Http\Request;

class LaporanMaterialViewer extends Controller
{
    public function indexPeriodLaporanMaterial($tanggalAwal, $tanggalAkhir, $idPmg)
    {
        $data = LaporanMaterial::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
            ->where('pmg_id', $idPmg)
            ->with('itemMaterial.jenisLaporan', 'pmg')
            ->get();

        if ($data->isEmpty()) {
            return null;
        }

        $norma = NormaMaterial::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
            ->where('pmg_id', $idPmg)
            ->with('itemMaterial.jenisLaporan')
            ->get();

        if ($norma->isEmpty()) {
            return null;
        }

        $dataOlah = LaporanProduksi::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
            ->where('pmg_id', $idPmg)
            ->with(['itemProduksi.jenisLaporan', 'pmg'])
            ->get();

        if ($dataOlah->isEmpty()) {
            return null;
        }


        $groupedDataNorma = $norma->groupBy(function ($item) {
            return $item->itemMaterial->jenisLaporan->name ?? 'Unknown';
        });

        $resultNorma = $groupedDataNorma->map(function ($items, $jenisLaporanName) {
            return [
                'jenis_laporan' => $jenisLaporanName,
                'items' => $items->groupBy('item_material_id')->map(function ($materialItems, $itemId) {
                    return [
                        'item_material_id' => $itemId,
                        'name' => $materialItems->first()->itemMaterial->name,
                        'kategori' => $materialItems->first()->itemMaterial->kategori,
                        'totalQty' => $materialItems->sum('qty'), // Calculate totalQty
                        'details' => $materialItems->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'tanggal' => $item->tanggal,
                                'qty' => $item->qty,
                                'satuan' => $item->satuan,
                            ];
                        }),
                    ];
                })->values(),
            ];
        })->values();

        $groupedData = $data->groupBy(function ($item) {
            return $item->itemMaterial->jenisLaporan->name ?? 'Unknown'; // Group by jenisLaporan name
        });

        $result = $groupedData->map(function ($items, $jenisLaporanName) {
            $conditionOlah = $items->first()->itemMaterial->jenisLaporan->condition_olah ?? 'sum';

            $kategoriData = $items->groupBy(function ($item) {
                return $item->itemMaterial->kategori ?? 'Unknown';
            });

            $totalPemakaian = 0;
            $selisih = null;

            $hasProportion = $kategoriData->has('proportion');
            $outgoingFinalValue = $kategoriData->get('outgoing') ? $kategoriData->get('outgoing')->sum('qty') : 0;
            $proportionFinalValue = $hasProportion ? $kategoriData->get('proportion')->sum('qty') : 0;

            if ($hasProportion) {
                $totalPemakaian = $outgoingFinalValue + ($outgoingFinalValue * ($proportionFinalValue / 100));
            } else {
                $totalPemakaian = $outgoingFinalValue;
            }

            if ($kategoriData->has('incoming')) {
                $incomingFinalValue = $kategoriData->get('incoming')->sum('qty');
                $selisih = $incomingFinalValue - $totalPemakaian;
            }

            return [
                'jenis_laporan' => $jenisLaporanName,
                'totalPemakaian' => $totalPemakaian,
                'selisih' => $selisih,
                'kategori_data' => $kategoriData->map(function ($kategoriItems, $kategoriName) use ($conditionOlah) {
                    $materials = $kategoriItems->groupBy(function ($item) {
                        return $item->itemMaterial->name;
                    });

                    $finalValue = 0;
                    if ($kategoriName === 'incoming') {
                        if ($materials->count() === 1) {
                            // If only one material, use its totalQty
                            $finalValue = $materials->first()->sum('qty');
                        } else {
                            // If more than one material, calculate based on condition_olah
                            $totalQtys = $materials->map->sum('qty')->values();

                            switch ($conditionOlah) {
                                case 'use_higher':
                                    $finalValue = $totalQtys->max();
                                    break;
                                case 'use_lower':
                                    $finalValue = $totalQtys->min();
                                    break;
                                case 'difference':
                                    $finalValue = $totalQtys->max() - $totalQtys->min();
                                    break;
                                default: // 'sum' or any other case
                                    $finalValue = $totalQtys->sum();
                                    break;
                            }
                        }
                    } else {
                        $finalValue = $kategoriItems->sum('qty');
                    }

                    return [
                        'kategori' => $kategoriName,
                        'finalValue' => $finalValue,
                        'materials' => $materials->map(function ($materialItems, $materialName) {
                            return [
                                'name' => $materialName,
                                'totalQty' => $materialItems->sum('qty'),
                                'detail' => $materialItems->map(function ($item) {
                                    return [
                                        'id' => $item->id,
                                        'item_material_id' => $item->item_material_id,
                                        'tanggal' => $item->tanggal,
                                        'pmg_id' => $item->pmg_id,
                                        'qty' => $item->qty,
                                        'item_material' => [
                                            'id' => $item->itemMaterial->id,
                                            'name' => $item->itemMaterial->name,
                                            'kategori' => $item->itemMaterial->kategori,
                                        ],
                                        'pmg' => [
                                            'id' => $item->pmg->id,
                                            'nama' => $item->pmg->nama,
                                            'lokasi' => $item->pmg->lokasi,
                                        ],
                                    ];
                                }),
                            ];
                        })->values(),
                    ];
                })->values(),
            ];
        });

        return $dataOlah;
        // return [
        //     "laporan_material" => $result->values(),
        //     "norma" => $resultNorma
        // ];
    }
}
