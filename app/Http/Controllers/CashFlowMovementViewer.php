<?php

namespace App\Http\Controllers;

use App\Models\CashFlowMovement\CashFlowMovement;
use App\Models\CashFlowSchedule\CashFlowSchedule;
use App\Models\Penjualan\LaporanPenjualan;
use App\Models\Profitablity\Profitablity;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CashFlowMovementViewer extends Controller
{
    public function indexPeriodCashFlowMovement($tanggalAkhir)
    {
        $startOfLastYear = now()->create($tanggalAkhir)->subYear()->startOfYear();
        $endOfThisYear = now()->create($tanggalAkhir)->endOfYear();

        // Determine the last month for thisYear based on $tanggalAkhir
        $lastMonthThisYear = now()->create($tanggalAkhir)->month;

        $data = CashFlowMovement::whereBetween('tanggal', [$startOfLastYear, $endOfThisYear])
            ->with('kategori')
            ->get();

        if ($data->isEmpty()) {
            return null;
        }

        $thisYear = [
            'year' => $endOfThisYear->year,
            'data' => [],
        ];
        $lastYear = [
            'year' => $startOfLastYear->year,
            'data' => [],
        ];

        // Initialize months with empty details
        for ($month = 1; $month <= 12; $month++) {
            $thisYear['data'][] = [
                'month' => $month,
                'ending_cash_balanced' => 0,
                'detail' => [],
            ];
            $lastYear['data'][] = [
                'month' => $month,
                'ending_cash_balanced' => 0,
                'detail' => [],
            ];
        }

        foreach ($data as $item) {
            $month = now()->create($item->tanggal)->month;
            $year = now()->create($item->tanggal)->year;
            $entry = [
                'id' => $item->id,
                'name' => $item->kategori->name,
                'value' => (float)$item->value,
                'nilai' => $item->kategori->nilai,
            ];

            if ($year === $startOfLastYear->year) {
                $lastYear['data'][$month - 1]['detail'][] = $entry;
            } elseif ($year === $endOfThisYear->year) {
                $thisYear['data'][$month - 1]['detail'][] = $entry;
            }
        }

        $calculateEndingCashBalanced = function (&$yearData) {
            $previousEnding = 0;
            foreach ($yearData['data'] as &$monthData) {
                $monthTotal = array_reduce($monthData['detail'], function ($carry, $item) {
                    return $carry + ($item['nilai'] === 'positive' ? $item['value'] : -$item['value']);
                }, 0);

                $monthData['ending_cash_balanced'] = $previousEnding + $monthTotal;
                $previousEnding = $monthData['ending_cash_balanced'];
            }
        };

        $calculateEndingCashBalanced($thisYear);
        $calculateEndingCashBalanced($lastYear);

        // Truncate thisYear data up to $tanggalAkhir month
        $thisYear['data'] = array_slice($thisYear['data'], 0, $lastMonthThisYear);

        // Calculate latestCashBalance
        $lastMonthIndex = $lastMonthThisYear - 1; // Array index for the last month
        $latestCashBalance = [
            'value' => $thisYear['data'][$lastMonthIndex]['ending_cash_balanced'] ?? 0,
            'status' => 'none',
            'difference' => 0,
        ];

        if ($lastMonthIndex > 0) {
            $previousMonthValue = $thisYear['data'][$lastMonthIndex - 1]['ending_cash_balanced'] ?? 0;
            $currentMonthValue = $thisYear['data'][$lastMonthIndex]['ending_cash_balanced'];
            $latestCashBalance['difference'] = $currentMonthValue - $previousMonthValue;
            $latestCashBalance['status'] = $currentMonthValue > $previousMonthValue ? 'up' : ($currentMonthValue < $previousMonthValue ? 'down' : 'none');
        }

        return [
            'latestCashBalance' => $latestCashBalance,
            'thisYear' => $thisYear,
            'lastYear' => $lastYear,
        ];
    }

    public function indexPeriodCashFlowSchedule($tanggalAkhir)
    {
        $tanggal = Carbon::parse($tanggalAkhir);
        $year = $tanggal->year;
        $month = $tanggal->month;

        $data = CashFlowSchedule::whereYear('tanggal', $year)
            ->with('kategori','payStatus')
            ->get();

        if ($data->isEmpty()) {
            return null;
        }

        $groupedData = $data->groupBy(function ($item) {
            return Carbon::parse($item->tanggal)->format('Y-m');
        });

        $formattedData = [];

        foreach ($groupedData as $monthYear => $items) {
            $monthYearArray = explode('-', $monthYear);
            $month = (int) $monthYearArray[1];
            $year = (int) $monthYearArray[0];

            $kategoriGroup = $items->groupBy('kategori_id');

            foreach ($kategoriGroup as $kategoriId => $kategoriItems) {
                $kategori = $kategoriItems->first()->kategori;

                $filteredItems = $kategoriItems->filter(function ($item) {
                    return $item->payStatus->state === false;
                });

                $totalValue = $filteredItems->sum(function ($item) {
                    return (float) $item->value;
                });

                $kategoriIndex = collect($formattedData)->search(function ($item) use ($kategori) {
                    return $item['name'] === $kategori->name;
                });

                if ($kategoriIndex === false) {
                    $formattedData[] = [
                        'name' => $kategori->name,
                        'total' => 0,
                        'period' => []
                    ];
                    $kategoriIndex = count($formattedData) - 1;
                }

                $formattedData[$kategoriIndex]['period'][] = [
                    'month' => $month,
                    'year' => $year,
                    'total' => $totalValue,
                    'data' => $kategoriItems->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'kategori_id' => $item->kategori_id,
                            'name' => $item->name,
                            'tanggal' => $item->tanggal,
                            'value' => $item->value,
                            'pay_status_id' => $item->pay_status_id,
                            'created_at' => $item->created_at,
                            'updated_at' => $item->updated_at,
                            'kategori' => [
                                'id' => $item->kategori->id,
                                'name' => $item->kategori->name,
                            ],
                            'pay_status' => [
                                'id' => $item->payStatus->id,
                                'name' => $item->payStatus->name,
                                'state' => $item->payStatus->state,
                                'remark' => $item->payStatus->remark,
                            ]
                        ];
                    })
                ];

                $formattedData[$kategoriIndex]['total'] += $totalValue;
            }
        }

        foreach ($formattedData as &$kategoriData) {
            usort($kategoriData['period'], function ($a, $b) {
                return $a['month'] <=> $b['month'];
            });
        }

        return [
            'kategori' => $formattedData
        ];
    }

    public function indexPeriodProfitability($tanggalAkhir)
    {
        $akhir = Carbon::parse($tanggalAkhir);
        $thisYear = $akhir->year;
        $lastYear = $akhir->copy()->subYear()->year;

        $penjualan = LaporanPenjualan::whereYear('tanggal', '>=', $lastYear)
            ->with('product', 'customer')
            ->get()
            ->map(function ($item) {
                $item->value = $item->qty * $item->harga_satuan;
                return $item;
            });

        $pendapatanByMonth = $penjualan->groupBy(function ($item) {
                return Carbon::parse($item->tanggal)->format('Y-m');
            })->mapWithKeys(function ($items, $monthYear) {
                return [$monthYear => $items->sum('value')];
            });

        $data = Profitablity::whereYear('tanggal', '>=', $lastYear)
            ->with('kategori')
            ->get()
            ->groupBy(function ($item) {
                return Carbon::parse($item->tanggal)->format('Y-m');
            });

        $result = [
            'thisYear' => ['year' => $thisYear, 'months' => []],
            'lastYear' => ['year' => $lastYear, 'months' => []],
        ];

        // Process grouped data
        foreach ($data as $monthYear => $items) {
            [$year, $month] = explode('-', $monthYear);
            $month = (int)$month;

            $pendapatan = $pendapatanByMonth[$monthYear] ?? 0;
            $targetPendapatanRkap = $items->firstWhere('kategori.name', 'Target Pendapatan RKAP')?->value ?? 0;
            $labaKotor = $items->firstWhere('kategori.name', 'Laba Kotor')?->value ?? 0;
            $ebitda = $items->firstWhere('kategori.name', 'EBITDA')?->value ?? 0;
            $labaBersih = $items->firstWhere('kategori.name', 'Laba Bersih')?->value ?? 0;

            $gpmPercent = $pendapatan > 0 ? ($labaKotor / $pendapatan) * 100 : 0;
            $ebitdaPercent = $pendapatan > 0 ? ($ebitda / $pendapatan) * 100 : 0;
            $npmPercent = $pendapatan > 0 ? ($labaBersih / $pendapatan) * 100 : 0;

            $details = $items->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->kategori->name,
                'value' => (float)$item->value,
            ]);

            $monthData = compact(
                'month',
                'pendapatan',
                'targetPendapatanRkap',
                'labaKotor',
                'gpmPercent',
                'ebitda',
                'ebitdaPercent',
                'labaBersih',
                'npmPercent',
                'details'
            );

            $key = $year == $thisYear ? 'thisYear' : ($year == $lastYear ? 'lastYear' : null);
            if ($key) {
                $result[$key]['months'][] = $monthData;
            }
        }

        // Add missing months for lastYear
        for ($m = 1; $m <= 12; $m++) {
            if (!collect($result['lastYear']['months'])->pluck('month')->contains($m)) {
                $result['lastYear']['months'][] = [
                    'month' => $m,
                    'pendapatan' => 0,
                    'targetPendapatanRkap' => 0,
                    'labaKotor' => 0,
                    'gpmPercent' => 0,
                    'ebitda' => 0,
                    'ebitdaPercent' => 0,
                    'labaBersih' => 0,
                    'npmPercent' => 0,
                    'details' => [],
                ];
            }
        }

        // Calculate total values for thisYear
        $totalLabaKotorThisYear = $totalEbitdaThisYear = $totalLabaBersihThisYear = 0;
        foreach ($result['thisYear']['months'] as $monthData) {
            $totalLabaKotorThisYear += $monthData['labaKotor'];
            $totalEbitdaThisYear += $monthData['ebitda'];
            $totalLabaBersihThisYear += $monthData['labaBersih'];
        }

        // Get latest month and the month before it (last month)
        $latestMonthData = end($result['thisYear']['months']);
        $lastMonthData = count($result['thisYear']['months']) > 1 ? prev($result['thisYear']['months']) : null;

        // Prepare latestMonth data
        $latestMonth = [
            'year' => $thisYear,
            'month' => $latestMonthData['month'],
            'pendapatan' => $latestMonthData['pendapatan'],
            'targetPendapatanRkap' => $latestMonthData['targetPendapatanRkap'],
            'totalLabaKotor' => $totalLabaKotorThisYear,
            'labaKotorLastMonth' => $lastMonthData['labaKotor'] ?? 0,
            'labaKotor' => $latestMonthData['labaKotor'],
            'gpmPercentLastMonth' => $lastMonthData['gpmPercent'],
            'gpmPercent' => $latestMonthData['gpmPercent'],
            'totalEbitda' => $totalEbitdaThisYear,
            'ebitdaLastMonth' => $lastMonthData['ebitda'] ?? 0,
            'ebitda' => $latestMonthData['ebitda'],
            'ebitdaPercentLastMonth' => $lastMonthData['ebitdaPercent'],
            'ebitdaPercent' => $latestMonthData['ebitdaPercent'],
            'totallabaBersih' => $totalLabaBersihThisYear,
            'labaBersihLastMonth' => $lastMonthData['labaBersih'] ?? 0,
            'labaBersih' => $latestMonthData['labaBersih'],
            'npmPercentLastMonth' => $lastMonthData['npmPercent'],
            'npmPercent' => $latestMonthData['npmPercent'],
        ];

        $result['latestMonth'] = $latestMonth;

        // Sort months
        foreach (['thisYear', 'lastYear'] as $key) {
            usort($result[$key]['months'], fn($a, $b) => $a['month'] <=> $b['month']);
        }

        return $result;
    }



}
