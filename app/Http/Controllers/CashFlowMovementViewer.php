<?php

namespace App\Http\Controllers;

use App\Models\CashFlowMovement\CashFlowMovement;
use Illuminate\Http\Request;

class CashFlowMovementViewer extends Controller
{
    public function indexPeriodCashFlowMovement($tanggalAwal, $tanggalAkhir, $idPmg)
{
    $startOfLastYear = now()->create($tanggalAkhir)->subYear()->startOfYear(); // January 1st of the previous year
    $endOfThisYear = now()->create($tanggalAkhir)->endOfYear(); // December 31st of the current year

    // Fetch data between the calculated range
    $data = CashFlowMovement::whereBetween('tanggal', [$startOfLastYear, $endOfThisYear])
        ->where('pmg_id', $idPmg)
        ->with('kategori', 'pmg')
        ->get();

    // Prepare arrays for both years
    $thisYear = [
        'year' => $endOfThisYear->year,
        'data' => [],
    ];
    $lastYear = [
        'year' => $startOfLastYear->year,
        'data' => [],
    ];

    // Fill data into their respective years grouped by months
    for ($month = 1; $month <= 12; $month++) {
        $thisYear['data'][$month] = [
            'month' => $month,
            'detail' => [],
        ];
        $lastYear['data'][$month] = [
            'month' => $month,
            'detail' => [],
        ];
    }

    // Populate the fetched data into the respective year arrays
    foreach ($data as $item) {
        $month = now()->create($item->tanggal)->month;
        $year = now()->create($item->tanggal)->year;

        $entry = [
            'id' => $item->kategori_id,
            'name' => $item->kategori->name,
            'value' => (float)$item->value,
        ];

        if ($year === $startOfLastYear->year) {
            $lastYear['data'][$month]['detail'][] = $entry;
        } elseif ($year === $endOfThisYear->year) {
            $thisYear['data'][$month]['detail'][] = $entry;
        }
    }

    // Calculate Ending Cash Balanced for each month
    foreach ([$lastYear, $thisYear] as &$yearData) {
        foreach ($yearData['data'] as &$monthData) {
            $endingCashBalanced = array_reduce($monthData['detail'], function ($carry, $item) {
                return $carry + ($item['name'] === 'positive' ? $item['value'] : -$item['value']);
            }, 0);

            $monthData['detail'][] = [
                'id' => null,
                'name' => 'Ending Cash Balanced',
                'value' => $endingCashBalanced,
            ];
        }
    }

    return [
            'thisYear' => $thisYear,
            'lastYear' => $lastYear,
    ];
}


}
