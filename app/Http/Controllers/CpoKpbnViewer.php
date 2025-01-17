<?php

namespace App\Http\Controllers;

use App\Models\CPOKpbn\CpoKpbn;
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

        if ($data->isEmpty() || $kurs->isEmpty()) {
            return response()->json([
                'data' => null,
                'message' => 'No data or kurs found for the given period.',
            ]);
        }

        $averageTotal = round($data->avg('value'), 2);

        $latestCpoData = $data->sortByDesc('tanggal')->first();
        $latestCpoValue = $latestCpoData->value;
        $latestCpoDate = $latestCpoData->tanggal;

        $latestKursData = $kurs->sortByDesc('tanggal')->first();
        $latestKursValue = $latestKursData->value;

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
                $kursValue = $matchedKurs ? $matchedKurs->value : null;
                $valueAsing = $kursValue ? round(($item->value / $kursValue) * 1000, 2) : null;

                if ($valueAsing !== null) {
                    $totalAsingValues[] = $valueAsing; // Add valueAsing to total for global average
                }

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

        // Calculate overall averageAsingTotal
        $averageAsingTotal = round(collect($totalAsingValues)->avg(), 2);

        return [
            'averageTotal' => $averageTotal,
            'averageAsingTotal' => $averageAsingTotal,
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
                    ->orderBy('tanggal', 'asc') // Sort by tanggal in ascending order
                    ->get();

        if ($data->isEmpty()) {
            return null;
        }

        return $data;
    }



}
