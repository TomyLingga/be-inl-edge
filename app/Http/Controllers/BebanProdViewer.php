<?php

namespace App\Http\Controllers;

use App\Models\BebanProd\BebanProd;
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

        $groupedData = $data->groupBy('uraian_id')->map(function ($items) {
            return [
                'uraian' => $items->first()->uraian->nama,
                'totalValue' => $items->sum('value'),
                'pmg' => $items->first()->pmg->nama,
                'details' => $items
                    ->groupBy(function ($item) {
                        // Parse tanggal as a Carbon instance and format it by year and month
                        return Carbon::parse($item->tanggal)->format('Y-m');
                    })
                    ->map(function ($monthlyItems) {
                        // Get the latest entry in the group
                        return $monthlyItems->sortByDesc('tanggal')->first();
                    })
                    ->map(function ($item) {
                        // Map the details for each group
                        return [
                            'id' => $item->id,
                            'tanggal' => $item->tanggal,
                            'value' => $item->value,
                        ];
                    })
                    ->values(), // Convert to a plain array
            ];
        });

        $groupedData = $groupedData->values();

        $totalCost = $groupedData->sum('totalValue');

        return [
            'totalCost' => $totalCost,
            'detail' => $groupedData,
        ];
    }



    public function indexPeriodTargetProd($tanggalAwal, $tanggalAkhir, $idPmg)
    {
        $startMonth = date('m', strtotime($tanggalAwal));
        $endMonth = date('m', strtotime($tanggalAkhir));
        $startYear = date('Y', strtotime($tanggalAwal));
        $endYear = date('Y', strtotime($tanggalAkhir));

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

        return $data;

    }
}
