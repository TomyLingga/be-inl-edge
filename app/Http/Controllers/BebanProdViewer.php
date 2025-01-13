<?php

namespace App\Http\Controllers;

use App\Models\BebanProd\BebanProd;
use App\Models\Target\TargetProduksi;
use Illuminate\Support\Facades\DB;

class BebanProdViewer extends Controller
{
    public function indexPeriodBebanProd($tanggalAwal, $tanggalAkhir, $idPmg)
    {
        $data = BebanProd::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
                    ->where('pmg_id', $idPmg)
                    ->whereIn('id', function ($query) use ($tanggalAwal, $tanggalAkhir, $idPmg) {
                        $query->selectRaw('MAX(id)')
                            ->from('beban_prod')
                            ->whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
                            ->where('pmg_id', $idPmg)
                            ->groupBy('uraian_id');
                    })
                    ->with('uraian', 'pmg')
                    ->get();

        if ($data->isEmpty()) {
            return null;
        }

        $totalCost = $data->sum('value');


        $formattedData = $data->map(function ($item) {
            return [
                'id' => $item->id,
                'uraian' => $item->uraian->nama,
                'value' => $item->value,
                'pmg' => $item->pmg->nama,
            ];
        });

        return [
            'totalCost' => $totalCost,
            'detail' => $formattedData,
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
