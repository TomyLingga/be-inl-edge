<?php

namespace App\Http\Controllers;

use App\Models\BebanProd\BebanProd;
use App\Models\LaporanProduksi\LaporanProduksi;
use App\Models\Target\TargetProduksi;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BebanProdViewer extends Controller
{

    public function indexPeriodBebanProd($tanggalAwal, $tanggalAkhir, $idPmg)
    {
        $data = BebanProd::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
            ->where('pmg_id', $idPmg)
            ->with('uraian', 'pmg')
            ->get();

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


        // Calculate totalCost
        $totalCost = $data->sum('value');

        // Calculate totalHargaSatuan
        $totalHargaSatuan = $totalCost / $totalOlahRefinery;

        $groupedData = $data->groupBy('uraian_id')->map(function ($items) use ($totalOlahRefinery) {
            $totalValue = $items->sum('value');
            $hargaSatuan = $totalValue / $totalOlahRefinery;

            return [
                'uraian' => $items->first()->uraian->nama,
                'totalValue' => $totalValue,
                'hargaSatuan' => $hargaSatuan, // Add hargaSatuan
                'pmg' => $items->first()->pmg->nama,
                'details' => $items
                    ->groupBy(function ($item) {
                        return Carbon::parse($item->tanggal)->format('Y-m');
                    })
                    ->map(function ($monthlyItems) {
                        return $monthlyItems->sortByDesc('tanggal')->first();
                    })
                    ->map(function ($item) use ($hargaSatuan) {
                        return [
                            'id' => $item->id,
                            'tanggal' => $item->tanggal,
                            'value' => $item->value,
                            'hargaSatuan' => $hargaSatuan, // Add hargaSatuan for each detail
                        ];
                    })
                    ->values(),
            ];
        });

        $groupedData = $groupedData->values();

        return [
            'cpoOlah' => $totalOlahRefinery,
            'totalCost' => $totalCost,
            'totalHargaSatuan' => $totalHargaSatuan, // Add totalHargaSatuan to the response
            'detail' => $groupedData,
        ];
    }


    public function indexPeriodTargetProd($tanggalAwal, $tanggalAkhir, $idPmg)
    {
        $startDate = Carbon::parse($tanggalAwal);
        $endDate = Carbon::parse($tanggalAkhir);

        $startMonth = $startDate->month;
        $endMonth = $endDate->month;
        $startYear = $startDate->year;
        $endYear = $endDate->year;

        $data = TargetProduksi::select('uraian_id', DB::raw('SUM(value) as total_value'))
            ->where('pmg_id', $idPmg)
            ->where(function ($query) use ($startMonth, $endMonth, $startYear, $endYear) {
                $query->whereYear('tanggal', $startYear)
                    ->whereMonth('tanggal', '>=', $startMonth);

                if ($startYear != $endYear) {
                    $query->orWhere(function ($subQuery) use ($endYear, $endMonth) {
                        $subQuery->whereYear('tanggal', $endYear)
                            ->whereMonth('tanggal', '<=', $endMonth);
                    });
                } else {
                    $query->whereYear('tanggal', $startYear)
                        ->whereMonth('tanggal', '<=', $endMonth);
                }
            })
            ->groupBy('uraian_id')
            ->with('uraian')
            ->get();

        $dataAll = TargetProduksi::whereBetween('tanggal', [$startDate, $endDate])
                ->where('pmg_id', $idPmg)
                ->get();

        $dataOlah = LaporanProduksi::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
            ->where('pmg_id', $idPmg)
            ->with(['itemProduksi.jenisLaporan', 'pmg'])
            ->get();

        if ($dataOlah->isEmpty()) {
            $totalOlahRefinery = 0;
        }else{
            $totalOlahRefinery = $dataOlah
                ->filter(function ($item) {
                    return $item->itemProduksi->kategori === 'bahan_olah' &&
                        $item->itemProduksi->jenisLaporan->name === 'Refinery';
                })
                ->sum('qty');
        }

        if ($data->isEmpty()) {
            $resultData = [
                [
                    'nama'  => 'CPO Olah',
                    'value' => number_format($totalOlahRefinery, 2, '.', ''), // Default value for CPO Olah
                ],
                [
                    'nama'  => 'RKAP',
                    'value' => number_format(0, 2, '.', ''), // Default value for CPO Olah
                    'percentage' => number_format(100, 2, '.', ''), // Default percentage
                ],
                [
                    'nama'     => 'Kapasitas Utility',
                    'value'    => number_format(0, 2, '.', ''), // Default target value
                    'percentage' => number_format(100, 2, '.', ''), // Default percentage
                ],
            ];

            return [
                    'cpoOlah' => 0,
                    'summary' => $resultData,
                    'detail'  => [], // No details since there's no data
            ];
        }

        $resultData = [
            [
                'nama'  => 'CPO Olah',
                'value' => number_format($totalOlahRefinery, 2, '.', ''),
            ],
        ];

        foreach ($data as $item) {
            $percentage = ($totalOlahRefinery / $item->total_value) * 100;
            $resultData[] = [
                'nama'     => $item->uraian->nama,
                'value'    => number_format($item->total_value, 2, '.', ''),
                'percentage' => number_format($percentage, 2, '.', ''),
            ];
        }

        return [
            'cpoOlah' => $totalOlahRefinery,
            'summary' => $resultData,
            'detail' => $dataAll,
        ];
    }

}
