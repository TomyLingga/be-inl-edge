<?php

namespace App\Http\Controllers;

use App\Models\CPOKpbn\CpoKpbn;
use App\Models\IncomingCpo\IncomingCpo;
use App\Models\IncomingCpo\TargetIncomingCpo;
use App\Models\Kurs\Kurs;
use App\Models\Kurs\MataUang;
use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use DateTime;
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
        $data = collect();

        while ($data->isEmpty() && strtotime($tanggalAkhir) >= strtotime($tanggalAwal)) {
            $fetchedData = $this->getHargaKpbn($tanggalAwal, $tanggalAkhir);
            $data = collect($fetchedData)->filter(fn($item) => $item['Prod_Code'] === 'CPO')->values();

            if ($data->isEmpty()) {
                $tanggalAkhir = date('Y-m-d', strtotime($tanggalAkhir . ' -1 day'));
            }
        }

        if ($data->isEmpty()) {
            return [
                "data" => null,
                "message" => "No Data Found, Even After Trying Previous Dates"
            ];
        }

        $kurs = $this->indexPeriodKurs($tanggalAwal, $tanggalAkhir, $idMataUang);

        if (empty($kurs)) {
            $kurs = collect([['value' => 0]]);
        } else {
            $kurs = collect($kurs);
        }

        $averageTotal = round($data->avg(fn($item) => (float) $item['Penetapan_Harga']), 2);
        $averageKurs = round($kurs->avg('value'), 2);

        $latestCpoData = $data->sortByDesc('Tanggal')->first();
        $latestCpoValue = round($latestCpoData['Penetapan_Harga'], 2);
        $latestCpoDate = $latestCpoData['Tanggal'];

        $latestKursData = $kurs->sortByDesc('tanggal')->first();
        $latestKursValue = $latestKursData['value'];

        $groupedData = $data->groupBy(fn($item) => Carbon::parse($item['Tanggal'])->format('Y-m'));

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
                    'valueAsing' => $valueAsing,
                ];
            });

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
        $mataUang = MataUang::findOrFail($idMataUang);

        $url = env('URL_KURS').$mataUang->name."&startdate={$tanggalAwal}&enddate={$tanggalAkhir}";
        // $url = "https://www.bi.go.id/biwebservice/wskursbi.asmx/getSubKursAsing3?mts={$mataUang->name}&startdate={$tanggalAwal}&enddate={$tanggalAkhir}";
        $xmlString = file_get_contents($url);

        $xml = simplexml_load_string($xmlString);

        $data = [];
        foreach ($xml->children('diffgr', true)->diffgram->children()->NewDataSet->children() as $table) {
            $beli = (float) $table->beli_subkursasing;
            $jual = (float) $table->jual_subkursasing;
            $tgl = date('Y-m-d', strtotime((string) $table->tgl_subkursasing));

            $data[] = [
                'id_mata_uang' => $mataUang->id,
                'tanggal' => $tgl,
                'value' => ($beli + $jual) / 2,
                'mata_uang' => [
                    'id' => $mataUang->id,
                    'name' => $mataUang->name,
                    'symbol' => $mataUang->symbol,
                    'remark' => $mataUang->remark,
                ],
            ];
        }

        usort($data, function ($a, $b) {
            return strtotime($a['tanggal']) - strtotime($b['tanggal']);
        });

        return $data;
    }
    // public function indexPeriodKurs($tanggalAwal, $tanggalAkhir, $idMataUang)
    // {
    //     $mataUang = MataUang::findOrFail($idMataUang);

    //     $url = "https://www.bi.go.id/biwebservice/wskursbi.asmx/getSubKursAsing3?mts={$mataUang->name}&startdate={$tanggalAwal}&enddate={$tanggalAkhir}";
    //     $xmlString = file_get_contents($url);
    //     $xml = simplexml_load_string($xmlString);

    //     $data = [];
    //     $prevValue = null;

    //     // Collect data from XML
    //     foreach ($xml->children('diffgr', true)->diffgram->children()->NewDataSet->children() as $table) {
    //         $beli = (float) $table->beli_subkursasing;
    //         $jual = (float) $table->jual_subkursasing;
    //         $tgl = date('Y-m-d', strtotime((string) $table->tgl_subkursasing));
    //         $value = ($beli + $jual) / 2;

    //         $data[$tgl] = [
    //             'id_mata_uang' => $mataUang->id,
    //             'tanggal' => $tgl,
    //             'value' => $value,
    //             'mata_uang' => [
    //                 'id' => $mataUang->id,
    //                 'name' => $mataUang->name,
    //                 'symbol' => $mataUang->symbol,
    //                 'remark' => $mataUang->remark,
    //             ],
    //         ];

    //         $prevValue = $value;
    //     }

    //     $filledData = [];
    //     $period = new DatePeriod(new DateTime($tanggalAwal), new DateInterval('P1D'), (new DateTime($tanggalAkhir))->modify('+1 day'));

    //     foreach ($period as $date) {
    //         $tgl = $date->format('Y-m-d');
    //         if (isset($data[$tgl])) {
    //             $filledData[] = $data[$tgl];
    //             $prevValue = $data[$tgl]['value'];
    //         } else {
    //             $filledData[] = [
    //                 'id_mata_uang' => $mataUang->id,
    //                 'tanggal' => $tgl,
    //                 'value' => $prevValue ?? 0,
    //                 'mata_uang' => [
    //                     'id' => $mataUang->id,
    //                     'name' => $mataUang->name,
    //                     'symbol' => $mataUang->symbol,
    //                     'remark' => $mataUang->remark,
    //                 ],
    //             ];
    //         }
    //     }

    //     return $filledData;
    // }

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

            $targetForMonth = optional($target->get($key))->sum('qty');
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
