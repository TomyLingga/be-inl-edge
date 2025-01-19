<?php

namespace App\Http\Controllers;

use App\Models\Kurs\Kurs;
use App\Models\LevyReuter\LevyDuty;
use App\Models\LevyReuter\MarketReuters;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LevyReutersViewer extends Controller
{
    public function indexPeriodLevyDuty($tanggalAwal, $tanggalAkhir, $idMataUang)
    {
        // Fetch data from the database
        $levyduty = LevyDuty::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
            ->where('id_mata_uang', $idMataUang)
            ->with('product', 'mataUang')
            ->orderBy('tanggal', 'asc') // Ensure levyduty is sorted by tanggal
            ->get();

        $marketReuters = MarketReuters::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
            ->where('id_mata_uang', $idMataUang)
            ->with('product', 'mataUang')
            ->orderBy('tanggal', 'asc') // Ensure marketReuters is sorted by tanggal
            ->get();

        $kurs = Kurs::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
            ->where('id_mata_uang', $idMataUang)
            ->with('mataUang')
            ->orderBy('tanggal', 'asc') // Ensure kurs is sorted by tanggal
            ->get();

        $averageKurs = round($kurs->avg('value'), 2);

        // Group data by Year and Month
        $groupedData = [];

        // Group kurs by year and month
        foreach ($kurs as $k) {
            $year = Carbon::parse($k->tanggal)->year;
            $month = Carbon::parse($k->tanggal)->month;
            $groupedData[$year]['months'][$month]['kurs'][] = $k;
        }

        // Group levyduty and marketReuters by year, month, and product
        foreach ($levyduty as $ld) {
            $year = Carbon::parse($ld->tanggal)->year;
            $month = Carbon::parse($ld->tanggal)->month;
            $productName = $ld->product->name;

            $groupedData[$year]['months'][$month]['products'][$productName]['levyduty'][] = $ld;
        }

        foreach ($marketReuters as $mr) {
            $year = Carbon::parse($mr->tanggal)->year;
            $month = Carbon::parse($mr->tanggal)->month;
            $productName = $mr->product->name;

            $groupedData[$year]['months'][$month]['products'][$productName]['marketReuters'][] = $mr;
        }

        // Calculate marketReutersExcldLevyDuty and marketIdr
        // Calculate marketReutersExcldLevyDuty and marketIdr
    foreach ($groupedData as $year => &$data) {
        foreach ($data['months'] as $month => &$monthData) {
            // Check if 'products' exists and is an array, then loop through it
            if (isset($monthData['products']) && is_array($monthData['products'])) {
                foreach ($monthData['products'] as $productName => &$productData) {
                    // Convert levyduty and kurs arrays into collections for easier filtering
                    $levydutyCollection = collect($productData['levyduty']);
                    $kursCollection = collect($monthData['kurs']);

                    // Calculate marketReutersExcldLevyDuty and marketIdr for each product
                    foreach ($productData['marketReuters'] as $index => $marketReuters) {
                        $marketDate = $marketReuters['tanggal'];

                        // Find the corresponding levyDuty and kurs for the same date
                        $levyDuty = $levydutyCollection->firstWhere('tanggal', $marketDate);
                        $kursValue = $kursCollection->firstWhere('tanggal', $marketDate);

                        if ($levyDuty && $kursValue) {
                            // Calculate marketReutersExcldLevyDuty
                            $marketReutersExcldLevyDuty = $marketReuters['nilai'] - $levyDuty['nilai'];

                            // Calculate marketIdr
                            $marketIdr = ($marketReutersExcldLevyDuty * $kursValue['value']) / 1000;

                            // Store the calculated values
                            $productData['marketReutersExcldLevyDuty'][$index] = [
                                'tanggal' => $marketDate,
                                'nilai' => $marketReutersExcldLevyDuty
                            ];

                            $productData['marketIdr'][$index] = [
                                'tanggal' => $marketDate,
                                'nilai' => $marketIdr
                            ];
                        }
                    }
                }
            }
        }
    }

        $output = [];
        foreach ($groupedData as $year => $data) {
            $output[] = [
                'year' => $year,
                'averageKurs' => $averageKurs,
                'months' => array_map(function ($monthData, $month) {
                    return [
                        'month' => $month,
                        'kurs' => $monthData['kurs'] ?? [],
                        'products' => array_map(function ($productData, $productName) {
                            return [
                                'product' => $productName,
                                'levyduty' => $productData['levyduty'] ?? [],
                                'marketReuters' => $productData['marketReuters'] ?? [],
                                'marketReutersExcldLevyDuty' => $productData['marketReutersExcldLevyDuty'] ?? [],
                                'marketIdr' => $productData['marketIdr'] ?? [],
                            ];
                        }, $monthData['products'] ?? [], array_keys($monthData['products'] ?? []))
                    ];
                }, $data['months'], array_keys($data['months']))
            ];
        }

        return $output;
    }


}
