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

        // Find the latest month with data (non-empty details)
        $latestMonthWithData = null;

        foreach (array_reverse($thisYear['data']) as $monthData) {
            if (!empty($monthData['detail'])) {
                $latestMonthWithData = $monthData;
                break;
            }
        }

        $latestMonth = [
            'year' => $endOfThisYear->year,
            'month' => $latestMonthWithData['month'] ?? $lastMonthThisYear,
            'data' => $latestMonthWithData ?? ['month' => $lastMonthThisYear, 'ending_cash_balanced' => 0, 'detail' => []],
        ];

        // Calculate latest cash balance
        $latestCashBalance = [
            'value' => $latestMonth['data']['ending_cash_balanced'] ?? 0,
            'status' => 'none',
            'difference' => 0,
        ];

        $lastMonthIndex = $latestMonthWithData ? $latestMonthWithData['month'] - 1 : $lastMonthThisYear - 1;

        if ($lastMonthIndex > 0) {
            $previousMonthValue = $thisYear['data'][$lastMonthIndex - 1]['ending_cash_balanced'] ?? 0;
        } else {
            $previousMonthValue = $lastYear['data'][11]['ending_cash_balanced'] ?? 0; // December of last year
        }

        $currentMonthValue = $latestCashBalance['value'];
        $latestCashBalance['difference'] = $currentMonthValue - $previousMonthValue;
        $latestCashBalance['status'] = $currentMonthValue > $previousMonthValue ? 'up' : ($currentMonthValue < $previousMonthValue ? 'down' : 'none');

        return [
            'latestCashBalance' => $latestCashBalance,
            'latestMonth' => $latestMonth,
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
                    'progress' => $filteredItems->map(function ($item) {
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
                    })->values(),
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

        foreach ($data as $monthYear => $items) {
            [$year, $month] = explode('-', $monthYear);
            $month = (int)$month;

            if ($year == $thisYear && $month > $akhir->month) {
                continue;
            }

            $pendapatan = $pendapatanByMonth[$monthYear] ?? 0;
            $targetPendapatanRkap = $items->firstWhere('kategori.name', 'Target Pendapatan RKAP')?->value ?? 0;
            $labaKotor = $items->firstWhere('kategori.name', 'Laba Kotor')?->value ?? 0;
            $targetLabaKotorRkap = $items->firstWhere('kategori.name', 'Target Laba Kotor RKAP')?->value ?? 0;
            $ebitda = $items->firstWhere('kategori.name', 'EBITDA')?->value ?? 0;
            $targetEbitdaRkap = $items->firstWhere('kategori.name', 'Target EBITDA RKAP')?->value ?? 0;
            $labaBersih = $items->firstWhere('kategori.name', 'Laba Bersih')?->value ?? 0;
            $targetLabaBersihRkap = $items->firstWhere('kategori.name', 'Target Laba Bersih RKAP')?->value ?? 0;

            $gpmPercent = $pendapatan > 0 ? min(100, ($labaKotor / $pendapatan) * 100) : 0;
            $ebitdaPercent = $pendapatan > 0 ? min(100, ($ebitda / $pendapatan) * 100) : 0;
            $npmPercent = $pendapatan > 0 ? min(100, ($labaBersih / $pendapatan) * 100) : 0;

            $gpmRkapPercent = $targetLabaKotorRkap > 0 ? min(100, ($labaKotor / $targetLabaKotorRkap) * 100) : 0;
            $ebitdaRkapPercent = $targetEbitdaRkap > 0 ? min(100, ($ebitda / $targetEbitdaRkap) * 100) : 0;
            $npmRkapPercent = $targetLabaBersihRkap > 0 ? min(100, ($labaBersih / $targetLabaBersihRkap) * 100) : 0;


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
                'targetLabaKotorRkap',
                'gpmRkapPercent',
                'ebitda',
                'ebitdaPercent',
                'targetEbitdaRkap',
                'ebitdaRkapPercent',
                'labaBersih',
                'npmPercent',
                'targetLabaBersihRkap',
                'npmRkapPercent',
                'details'
            );

            $key = $year == $thisYear ? 'thisYear' : ($year == $lastYear ? 'lastYear' : null);
            if ($key) {
                $result[$key]['months'][] = $monthData;
            }
        }

        for ($m = 1; $m <= 12; $m++) {
            if (!collect($result['lastYear']['months'])->pluck('month')->contains($m)) {
                $result['lastYear']['months'][] = [
                    'month' => $m,
                    'pendapatan' => 0,
                    'targetPendapatanRkap' => 0,
                    'labaKotor' => 0,
                    'gpmPercent' => 0,
                    'targetLabaKotorRkap' => 0,
                    'gpmRkapPercent' => 0,
                    'ebitda' => 0,
                    'ebitdaPercent' => 0,
                    'targetEbitdaRkap' => 0,
                    'ebitdaRkapPercent' => 0,
                    'labaBersih' => 0,
                    'npmPercent' => 0,
                    'targetLabaBersihRkap' => 0,
                    'npmRkapPercent' => 0,
                    'details' => [],
                ];
            }
        }

        $totalLabaKotorThisYear = $totalEbitdaThisYear = $totalLabaBersihThisYear = 0;
        foreach ($result['thisYear']['months'] as $monthData) {
            $totalLabaKotorThisYear += $monthData['labaKotor'];
            $totalEbitdaThisYear += $monthData['ebitda'];
            $totalLabaBersihThisYear += $monthData['labaBersih'];
        }

        foreach (['thisYear', 'lastYear'] as $key) {
            usort($result[$key]['months'], fn($a, $b) => $a['month'] <=> $b['month']);
        }

        $latestMonthData = collect($result['thisYear']['months'])->last();
        $lastMonthData = collect($result['thisYear']['months'])
            ->where('month', '<', $latestMonthData['month'])
            ->sortByDesc('month')
            ->first();

        if (!$lastMonthData) {
            $lastMonthData = collect($result['lastYear']['months'])->firstWhere('month', 12);
        }

        $latestMonth = [
            'year' => $thisYear,
            'month' => $latestMonthData['month'],
            'pendapatan' => $latestMonthData['pendapatan'] ?? 0,
            'targetPendapatanRkap' => $latestMonthData['targetPendapatanRkap'] ?? 0,
            'totalLabaKotor' => $totalLabaKotorThisYear ?? 0,
            'labaKotorLastMonth' => $lastMonthData['labaKotor'] ?? 0,
            'labaKotor' => $latestMonthData['labaKotor'] ?? 0,
            'gpmPercentLastMonth' => $lastMonthData['gpmPercent'] ?? 0,
            'gpmPercent' => $latestMonthData['gpmPercent'] ?? 0,
            'targetLabaKotorRkap' => $latestMonthData['targetLabaKotorRkap'] ?? 0,
            'gpmRkapPercent' => $latestMonthData['gpmRkapPercent'] ?? 0,
            'totalEbitda' => $totalEbitdaThisYear ?? 0,
            'ebitdaLastMonth' => $lastMonthData['ebitda'] ?? 0,
            'ebitda' => $latestMonthData['ebitda'] ?? 0,
            'ebitdaPercentLastMonth' => $lastMonthData['ebitdaPercent'] ?? 0,
            'ebitdaPercent' => $latestMonthData['ebitdaPercent'] ?? 0,
            'targetEbitdaRkap' => $latestMonthData['targetEbitdaRkap'] ?? 0,
            'ebitdaRkapPercent' => $latestMonthData['ebitdaRkapPercent'] ?? 0,
            'totallabaBersih' => $totalLabaBersihThisYear ?? 0,
            'labaBersihLastMonth' => $lastMonthData['labaBersih'] ?? 0,
            'labaBersih' => $latestMonthData['labaBersih'] ?? 0,
            'npmPercentLastMonth' => $lastMonthData['npmPercent'] ?? 0,
            'npmPercent' => $latestMonthData['npmPercent'] ?? 0,
            'targetLabaBersihRkap' => $latestMonthData['targetLabaBersihRkap'] ?? 0,
            'npmRkapPercent' => $latestMonthData['npmRkapPercent'] ?? 0,
        ];

        $result['latestMonth'] = $latestMonth;

        foreach (['thisYear', 'lastYear'] as $key) {
            usort($result[$key]['months'], fn($a, $b) => $a['month'] <=> $b['month']);
        }

        return $result;
    }



}
