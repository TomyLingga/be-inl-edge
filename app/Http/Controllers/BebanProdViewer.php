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

        if ($data->isEmpty()) {
            return null;
        }

        $dataOlah = LaporanProduksi::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
            ->where('pmg_id', $idPmg)
            ->with(['itemProduksi.jenisLaporan', 'pmg'])
            ->get();

        $totalOlahRefinery = $dataOlah->isEmpty() ? 1 : $dataOlah
            ->filter(function ($item) {
                return $item->itemProduksi->kategori === 'bahan_olah' &&
                    $item->itemProduksi->jenisLaporan->name === 'Refinery';
            })
            ->sum('qty');

        // Filter for the latest data by month
        $latestData = $data->groupBy('uraian_id')->map(function ($items) {
            return $items
                ->groupBy(function ($item) {
                    return Carbon::parse($item->tanggal)->format('Y-m');
                })
                ->map(function ($monthlyItems) {
                    return $monthlyItems->sortByDesc('tanggal')->first(); // Get the latest entry for each month
                });
        });

        // Flatten the grouped data to calculate totals
        $flatData = $latestData->flatMap(function ($monthlyItems) {
            return $monthlyItems;
        });

        $totalCost = $flatData->sum('value');
        $totalHargaSatuan = $totalCost / $totalOlahRefinery;

        $groupedData = $latestData->map(function ($monthlyItems) use ($totalOlahRefinery) {
            $totalValue = $monthlyItems->sum('value');
            $hargaSatuan = $totalValue / $totalOlahRefinery;

            return [
                'uraian' => $monthlyItems->first()->uraian->nama,
                'totalValue' => $totalValue,
                'hargaSatuan' => $hargaSatuan,
                'pmg' => $monthlyItems->first()->pmg->nama,
                'details' => $monthlyItems->map(function ($item) use ($hargaSatuan) {
                    return [
                        'id' => $item->id,
                        'tanggal' => $item->tanggal,
                        'value' => $item->value,
                        'hargaSatuan' => $hargaSatuan,
                    ];
                })->values(),
            ];
        });

        return [
            'cpoOlah' => $totalOlahRefinery,
            'totalCost' => $totalCost,
            'totalHargaSatuan' => $totalHargaSatuan,
            'detail' => $groupedData->values(),
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

        if ($data->isEmpty()) {
            return response()->json([
                'data' => [],
                'message' => 'No data found for the given period',
            ], 404);
        }

        $dataAll = TargetProduksi::whereBetween('tanggal', [$startDate, $endDate])
                ->where('pmg_id', $idPmg)
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
