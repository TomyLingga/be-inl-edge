<?php

namespace App\Http\Controllers;

use App\Models\BebanProd\BebanProd;

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
}
