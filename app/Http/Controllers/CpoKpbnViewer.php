<?php

namespace App\Http\Controllers;

use App\Models\CPOKpbn\CpoKpbn;
use App\Models\IncomingCpo\IncomingCpo;
use App\Models\IncomingCpo\TargetIncomingCpo;
use App\Models\Kurs\Kurs;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CpoKpbnViewer extends Controller
{
    public function getHargaKpbn($tanggalAwal, $tanggalAkhir)
    {
        $tokenKpbn = env('TOKEN_KPBN');
        $urlKpbn = env('URL_KPBN');

        return Http::withHeaders([
            'Authorization' => "Basic ".$tokenKpbn,
        ])->get($urlKpbn. "start_date=".$tanggalAwal."&end_date=".$tanggalAkhir)->json() ?? [];
    }

    public function indexPeriodCpoKpbn($tanggalAwal, $tanggalAkhir, $idMataUang)
    {
        $data = $this->getHargaKpbn($tanggalAwal, $tanggalAkhir);

        $data = collect($data)->filter(function ($item) {
            return $item['Prod_Code'] === 'CPO';
        })->values();

        if ($data->isEmpty()) {
            $tanggalAkhir = date('Y-m-d', strtotime($tanggalAkhir . ' -1 day'));
            $data = $this->getHargaKpbn($tanggalAwal, $tanggalAkhir);

            $data = collect($data)->filter(fn($item) => $item['Prod_Code'] === 'CPO')->values();
        }

        if ($data->isEmpty()) {
            return [
                "data" => null,
                "message" => "No Data Found, Even After Trying Previous Date"
            ];
        }

        $kurs = Kurs::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
                    ->where('id_mata_uang', $idMataUang)
                    ->orderBy('tanggal', 'asc')
                    ->get();

        if ($kurs->isEmpty()) {
            $kurs = collect([['value' => 0]]);
        }

        $averageTotal = round($data->avg(fn($item) => (float) $item['Penetapan_Harga']), 2);
        $averageKurs = round($kurs->avg('value'), 2);

        $latestCpoData = $data->sortByDesc('Tanggal')->first();
        $latestCpoValue = round($latestCpoData['Penetapan_Harga'], 2);
        $latestCpoDate = $latestCpoData['Tanggal'];

        $latestKursData = $kurs->sortByDesc('tanggal')->first();
        $latestKursValue = $latestKursData['value'];

        $groupedData = $data->groupBy(function ($item) {
            return Carbon::parse($item['Tanggal'])->format('Y-m');
        });

        $totalAsingValues = [];

        $result = [];
        foreach ($groupedData as $key => $items) {
            [$year, $month] = explode('-', $key);

            if (!isset($result[$year])) {
                $result[$year] = [
                    'year' => (int) $year,
                    'months' => []
                ];
            }

            $details = $items->map(function ($item) use ($kurs, &$totalAsingValues) {
                $matchedKurs = $kurs->where('tanggal', $item['Tanggal'])->first();
                $kursValue = $matchedKurs ? $matchedKurs['value'] : 0;
                $valueAsing = ($kursValue != 0) ? round(($item['Penetapan_Harga'] / $kursValue) * 1000, 2) : 0;
                $totalAsingValues[] = $valueAsing;

                return [
                    'tanggal' => $item['Tanggal'],
                    'value' => round($item['Penetapan_Harga'], 2),
                    'kurs' => $kursValue,
                    'valueAsing' => $valueAsing, // Value in Ton
                ];
            });

            // Calculate averageAsing for the month
            $averageAsing = round($details->pluck('valueAsing')->filter()->avg(), 2);

            $result[$year]['months'][] = [
                'month' => (int) $month,
                'average' => round($items->avg(fn($item) => (float) $item['Penetapan_Harga']), 2),
                'averageAsing' => $averageAsing,
                'detail' => $details,
            ];
        }

        $years = array_values($result);

        return [
            'averageTotal' => $averageTotal,
            'averageAsingTotal' => ($averageKurs != 0) ? round(($averageTotal / $averageKurs) * 1000, 2) : 0,
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

    public function indexPeriodIncomingCpo($tanggalAwal, $tanggalAkhir)
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
            $remaining = $targetForMonth - $monthQty ?? 0;

            $totalQty += $monthQty;
            $totalValue += $monthValue;

            $result[] = [
                'year' => (int) $year,
                'month' => (int) $month,
                'monthQty' => $monthQty ?? 0,
                'monthValue' => $monthValue ?? 0,
                'target' => $targetForMonth ?? 0,
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

    public function indexPeriodTargetIncomingCpo($tanggalAwal, $tanggalAkhir)
    {
        $data = TargetIncomingCpo::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
            ->orderBy('tanggal', 'asc')
            ->get();

        if ($data->isEmpty()) {
            return response()->json([
                'data' => null,
                'message' => 'No data found for the given period.',
            ]);
        }

        return $data;
    }
}
