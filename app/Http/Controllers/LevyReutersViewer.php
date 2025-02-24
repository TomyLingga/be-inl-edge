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
        $levyduty = LevyDuty::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
            ->where('id_mata_uang', $idMataUang)
            ->with('product', 'mataUang')
            ->orderBy('tanggal', 'asc')
            ->get();

        $marketReuters = MarketReuters::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
            ->where('id_mata_uang', $idMataUang)
            ->with('product', 'mataUang')
            ->orderBy('tanggal', 'asc')
            ->get();

        $cpoKpbnViewer = new CpoKpbnViewer();

        $kurs = $cpoKpbnViewer->indexPeriodKurs($tanggalAwal, $tanggalAkhir, $idMataUang);

        if (empty($kurs)) {
            $kurs = collect([(object) ['tanggal' => null, 'value' => 0]]);
        } else {
            $kurs = collect($kurs)->map(fn($item) => (object) $item);
        }

        $averageKurs = round($kurs->avg('value'), 2);

        $groupedData = [];

        foreach ($kurs as $k) {
            $year = Carbon::parse($k->tanggal)->year;
            $month = Carbon::parse($k->tanggal)->month;
            $groupedData[$year]['months'][$month]['kurs'][] = $k;
        }

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

        foreach ($groupedData as $year => &$data) {
            $averageMarketReutersExcldLevyDuty = [];
            $averageMarketIdr = [];
            foreach ($data['months'] as $month => &$monthData) {
                $monthData['kurs'] = $monthData['kurs'] ?? [];
                if (isset($monthData['products']) && is_array($monthData['products'])) {
                    foreach ($monthData['products'] as $productName => &$productData) {
                        // Convert levyduty and kurs arrays into collections for easier filtering
                        $levydutyCollection = collect($productData['levyduty']);
                        $kursCollection = collect($monthData['kurs']);

                        // Calculate marketReutersExcldLevyDuty and marketIdr for each product
                        foreach ($productData['marketReuters'] as $index => $marketReuters) {
                            $marketDate = $marketReuters['tanggal'];
                            $marketProduct = $marketReuters['product']['name'];

                            // Find the corresponding levyDuty and kurs for the same date
                            $levyDuty = $levydutyCollection->firstWhere('tanggal', $marketDate);
                            $kursValue = $kursCollection->firstWhere('tanggal', $marketDate);

                            if (!$kursValue) {
                                $kursValue = (object) ['value' => 0]; // Ensure it's always an object
                            }

                            if ($levyDuty && $kursValue->value !== 0) {
                                // Calculate marketReutersExcldLevyDuty
                                $marketReutersExcldLevyDuty = $marketReuters['nilai'] - $levyDuty['nilai'];

                                // Calculate marketIdr
                                $marketIdr = ($marketReutersExcldLevyDuty * $kursValue->value) / 1000;

                                // Store the calculated values
                                $productData['marketReutersExcldLevyDuty'][$index] = [
                                    'product' => $marketProduct,
                                    'tanggal' => $marketDate,
                                    'nilai' => $marketReutersExcldLevyDuty
                                ];

                                $productData['marketIdr'][$index] = [
                                    'product' => $marketProduct,
                                    'tanggal' => $marketDate,
                                    'nilai' => $marketIdr
                                ];
                            }
                        }

                        $excldLevyDutyValues = collect($productData['marketReutersExcldLevyDuty'])->pluck('nilai');
                        $idrValues = collect($productData['marketIdr'])->pluck('nilai');

                        // Calculate averages and store them grouped by product
                        if ($excldLevyDutyValues->isNotEmpty()) {
                            if (!isset($averageMarketReutersExcldLevyDuty[$productName])) {
                                $averageMarketReutersExcldLevyDuty[$productName] = [];
                            }
                            $averageMarketReutersExcldLevyDuty[$productName][] = $excldLevyDutyValues->avg();
                        }
                        if ($idrValues->isNotEmpty()) {
                            if (!isset($averageMarketIdr[$productName])) {
                                $averageMarketIdr[$productName] = [];
                            }
                            $averageMarketIdr[$productName][] = $idrValues->avg();
                        }
                    }
                }
            }

        }

        $data['averageMarketReutersExcldLevyDuty'] = collect($averageMarketReutersExcldLevyDuty)->map(function ($values, $productName) {
            return [
                'product' => $productName,
                'avg' => round(collect($values)->avg(), 2)
            ];
        })->values()->toArray();

        $data['averageMarketIdr'] = collect($averageMarketIdr)->map(function ($values, $productName) {
            return [
                'product' => $productName,
                'avg' => round(collect($values)->avg(), 2)
            ];
        })->values()->toArray();

        $output = [];
        foreach ($groupedData as $year => $data) {
            $output[] = [
                'year' => $year,
                'averageKurs' => $averageKurs,
                'averageMarketReutersExcldLevyDuty' => $data['averageMarketReutersExcldLevyDuty'],
                'averageMarketIdr' => $data['averageMarketIdr'],
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
