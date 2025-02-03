<?php

namespace App\Http\Controllers;

use App\Models\CPOKpbn\CpoKpbn;
use App\Models\IncomingCpo\IncomingCpo;
use App\Models\IncomingCpo\TargetIncomingCpo;
use App\Models\Kurs\Kurs;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CpoKpbnViewer extends Controller
{
    public function indexPeriodCpoKpbn($tanggalAwal, $tanggalAkhir, $idMataUang)
    {
        $data = CpoKpbn::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])->get();

        $kurs = Kurs::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
                    ->where('id_mata_uang', $idMataUang)
                    ->orderBy('tanggal', 'asc') // Ensure kurs is sorted by tanggal
                    ->get();

        // Set default kurs if empty
        if ($kurs->isEmpty()) {
            $kurs = collect([['value' => 1]]); // Default kurs value to 1
        }

        if ($data->isEmpty()) {
            return null;
        }

        $averageTotal = round($data->avg('value'), 2);
        $averageKurs = round($kurs->avg('value'), 2);

        $latestCpoData = $data->sortByDesc('tanggal')->first();
        $latestCpoValue = $latestCpoData->value;
        $latestCpoDate = $latestCpoData->tanggal;

        $latestKursData = $kurs->sortByDesc('tanggal')->first();
        $latestKursValue = $latestKursData['value'];

        $groupedData = $data->groupBy(function ($item) {
            return Carbon::parse($item->tanggal)->format('Y-m');
        });

        $totalAsingValues = []; // Collect all valueAsing for averageAsingTotal

        $result = [];
        foreach ($groupedData as $key => $items) {
            [$year, $month] = explode('-', $key);

            if (!isset($result[$year])) {
                $result[$year] = [
                    'year' => (int) $year,
                    'months' => []
                ];
            }

            // Map details and calculate valueAsing for the month
            $details = $items->map(function ($item) use ($kurs, &$totalAsingValues) {
                $matchedKurs = $kurs->where('tanggal', $item->tanggal)->first();
                $kursValue = $matchedKurs ? $matchedKurs['value'] : 1; // Default to 1 if not found
                $valueAsing = round(($item->value / $kursValue) * 1000, 2);

                $totalAsingValues[] = $valueAsing;

                return [
                    'id' => $item->id,
                    'tanggal' => $item->tanggal,
                    'value' => $item->value,
                    'kurs' => $kursValue,
                    'valueAsing' => $valueAsing, // Value in Ton
                ];
            });

            // Calculate averageAsing for the month
            $averageAsing = round($details->pluck('valueAsing')->filter()->avg(), 2);

            $result[$year]['months'][] = [
                'month' => (int) $month,
                'average' => round($items->avg('value'), 2),
                'averageAsing' => $averageAsing,
                'detail' => $details,
            ];
        }

        $years = array_values($result);

        return [
            'averageTotal' => $averageTotal,
            'averageAsingTotal' => $averageTotal / $averageKurs * 1000,
            'averageKurs' => $averageKurs,
            'latestCpoValue' => $latestCpoValue,
            'latestCpoDate' => $latestCpoDate,
            'latestKursValue' => $latestKursValue,
            'latestKursDate' => $latestCpoDate,
            'years' => $years,
        ];
    }


    public function indexPeriodKurs($tanggalAwal, $tanggalAkhir, $idMataUang)
    {
        $data = Kurs::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
                    ->where('id_mata_uang',$idMataUang)
                    ->with('mataUang')
                    ->orderBy('tanggal', 'asc') // Sort by tanggal in ascending order
                    ->get();

        if ($data->isEmpty()) {
            return null;
        }

        return $data;
    }

    public function indexPeriodIncomingCpo($tanggalAwal, $tanggalAkhir, $idMataUang)
    {
        $data = IncomingCpo::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
            ->with('source') // Load the related source
            ->orderBy('tanggal', 'asc')
            ->get();

        if ($data->isEmpty()) {
            return response()->json([
                'data' => null,
                'message' => 'No data found for the given period.',
            ]);
        }

        $target = TargetIncomingCpo::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
            ->get()
            ->groupBy(function ($item) {
                return Carbon::parse($item->tanggal)->format('Y-m');
            });

        $groupedData = $data->groupBy(function ($item) {
            return Carbon::parse($item->tanggal)->format('Y-m');
        });

        $totalQty = 0;
        $totalValue = 0;

        $result = [];
        foreach ($groupedData as $key => $items) {
            [$year, $month] = explode('-', $key);

            $monthQty = $items->sum('qty');
            $monthValue = $items->sum(function ($item) {
                return $item->qty * $item->harga;
            });

            $targetForMonth = optional($target->get($key))->sum('qty'); // Get target for this month
            $remaining = $targetForMonth - $monthQty;

            $totalQty += $monthQty;
            $totalValue += $monthValue;

            $result[] = [
                'year' => (int) $year,
                'month' => (int) $month,
                'monthQty' => $monthQty,
                'monthValue' => $monthValue,
                'target' => $targetForMonth,
                'remaining' => $remaining,
                'detail' => $items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'tanggal' => $item->tanggal,
                        'qty' => $item->qty,
                        'harga' => $item->harga,
                        'value' => $item->qty * $item->harga,
                        'source_id' => $item->source_id,
                        'created_at' => $item->created_at,
                        'updated_at' => $item->updated_at,
                        'source' => [
                            'id' => $item->source->id,
                            'name' => $item->source->name,
                        ],
                    ];
                }),
            ];
        }

        return [
            'totalQty' => $totalQty,
            'totalValue' => $totalValue,
            'data' => $result,
        ];
    }




}
