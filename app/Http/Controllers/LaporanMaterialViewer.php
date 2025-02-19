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
            $totalOlahRefinery = 1;
        }else{
            $totalOlahRefinery = $dataOlah
                ->filter(function ($item) {
                    return $item->itemProduksi->kategori === 'bahan_olah' &&
                        $item->itemProduksi->jenisLaporan->name === 'Refinery';
                })
                ->sum('qty');
        }

        $groupedDataNorma = $norma->groupBy(function ($item) {
            return $item->itemMaterial->jenisLaporan->name ?? 'Unknown';
        });

        $resultNorma = $groupedDataNorma->map(function ($items, $jenisLaporanName) {
            return [
                'jenis_laporan' => $jenisLaporanName,
                'materials' => $items->groupBy('item_material_id')->map(function ($materialItems, $itemId) {
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

        $result = $groupedData->map(function ($items, $jenisLaporanName) use ($resultNorma, $totalOlahRefinery) {
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
                'kategori_data' => $kategoriData->map(function ($kategoriItems, $kategoriName) use ($conditionOlah, $resultNorma, $totalOlahRefinery) {
                    $materials = $kategoriItems->groupBy(function ($item) {
                        return $item->itemMaterial->name;
                    });

                    $finalValue = 0;
                    if ($kategoriName === 'incoming') {
                        if ($materials->count() === 1) {
                            $finalValue = $materials->first()->sum('qty');
                        } else {
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
                                default:
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
                        'materials' => $materials->map(function ($materialItems, $materialName) use ($resultNorma, $totalOlahRefinery) {
                            $totalQty = $materialItems->sum('qty');

                            // Find matching norma
                            $matchingNorma = $resultNorma->flatMap(function ($norma) {
                                return $norma['materials'];
                            })->firstWhere('name', $materialName);

                            $normaValue = $matchingNorma['totalQty'] ?? null;

                            $usage = $totalOlahRefinery > 0 ? $totalQty / ($totalOlahRefinery / 1000) : 0;

                            // Determine color
                            $color = null;
                            if (!is_null($normaValue)) {
                                $color = $normaValue < $usage ? 'red' : 'green';
                            }

                            return [
                                'name' => $materialName,
                                'totalQty' => $totalQty,
                                'norma' => $normaValue,
                                'usage' => round($usage, 6),
                                'color' => $color,
                                'detail' => $materialItems->map(function ($item) {
                                    return [
                                        'id' => $item->id,
                                        'item_material_id' => $item->item_material_id,
                                        'tanggal' => $item->tanggal,
                                        'pmg_id' => $item->pmg_id,
                                        'qty' => $item->qty,
                                    ];
                                }),
                            ];
                        })->values(),
                    ];
                })->values(),
            ];
        });

        return [
            "totalOlahRefinery" => $totalOlahRefinery,
            "laporan_material" => $result->values(),
            "norma" => $resultNorma
        ];
    }

    public function indexPeriodNormaMaterial($tanggalAwal, $tanggalAkhir, $idPmg)
    {
        $norma = NormaMaterial::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
            ->where('pmg_id', $idPmg)
            ->with('itemMaterial.jenisLaporan')
            ->get();

        if ($norma->isEmpty()) {
            return null;
        }

        return $norma;
    }
}
