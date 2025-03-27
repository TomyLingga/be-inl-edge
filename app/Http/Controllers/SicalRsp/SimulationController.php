<?php

namespace App\Http\Controllers\SicalRsp;

use App\Http\Controllers\Controller;
use App\Models\Master\Product;
use App\Models\SicalRsp\Catatan;
use App\Models\SicalRsp\Cost;
use App\Models\SicalRsp\DetailCatatan;
use App\Models\SicalRsp\Dmo;
use App\Models\SicalRsp\MasterCost;
use App\Models\SicalRsp\Offer;
use App\Models\SicalRsp\Pengali;
use App\Models\SicalRsp\Simulation;
use App\Models\SicalRsp\Utilisasi;
use App\Services\LoggerService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SimulationController extends Controller
{
    private $messageFail = 'Something went wrong';
    private $messageMissing = 'Data not found in record';
    private $messageAll = 'Success to Fetch All Datas';
    private $messageSuccess = 'Success to Fetch Data';
    private $messageCreate = 'Success to Create Data';
    private $messageUpdate = 'Success to Update Data';

    public function index()
    {
        try {
            $data = Simulation::with([
                'product',
                'dmo',
                'offer',
                'catatan.detailCatatan',  // Include detailCatatan inside catatan
                'cost.masterCost'         // Include masterCost inside cost
            ])->get();

            if ($data->isEmpty()) {
                return response()->json(['message' => $this->messageMissing], 401);
            }

            return response()->json(['data' => $data, 'message' => $this->messageAll], 200);
        } catch (QueryException $e) {
            return response()->json([
                'message' => $this->messageFail,
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $data = Simulation::with([
                'product',
                'dmo',
                'offer',
                'catatan.detailCatatan',  // Include detailCatatan inside catatan
                'cost.masterCost'         // Include masterCost inside cost
            ])->findOrFail($id);

            $data->history = $this->formatLogs($data->logs);
            unset($data->logs);

            return response()->json([
                'data' => $data,
                'message' => $this->messageSuccess,
                'code' => 200
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'message' => $this->messageFail,
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $rules = [
                'product_id' => 'required|exists:' . Product::class . ',id',
                'name' => 'required|unique:simulation,name',
                'kurs' => 'required|numeric',
                'expected_margin' => 'required|numeric',
                'id_dmo' => 'required|exists:' . Dmo::class . ',id',
                'offer.buyer_name' => 'required|string',
                'offer.price' => 'required|numeric',
                'offer.volume' => 'required|numeric',

                // Costs Validation
                'costs' => 'required|array|min:1',
                'costs.*.id_master_cost' => 'required|exists:master_costs,id',
                'costs.*.value' => 'required|numeric',
                'costs.*.id_utilisasi' => 'required|exists:utilisasi,id',

                // Catatan Validation
                'catatan' => 'required|array|min:1',
                'catatan.*.judul' => 'required|string',

                // DetailCatatan Validation
                'catatan.*.detailCatatan' => 'required|array|min:1',
                'catatan.*.detailCatatan.*.teks' => 'required|string',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'success' => false,
                ], 400);
            }

            // Create Simulation
            $simulation = Simulation::create([
                'product_id' => $request->product_id,
                'name' => $request->name,
                'date' => now()->format('Y-m-d'),
                'kurs' => $request->kurs,
                'expected_margin' => $request->expected_margin,
                'id_dmo' => $request->id_dmo,
            ]);

            // Create Offer
            $offer = Offer::create([
                'buyer_name' => $request->offer['buyer_name'],
                'price' => $request->offer['price'],
                'volume' => $request->offer['volume'],
                'simulation_id' => $simulation->id,
            ]);

            // Insert Multiple Costs
            $costs = [];
            foreach ($request->costs as $costData) {
                $costs[] = [
                    'id_master_cost' => $costData['id_master_cost'],
                    'value' => $costData['value'],
                    'id_utilisasi' => $costData['id_utilisasi'], // Now always required
                    'id_simulation' => $simulation->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            Cost::insert($costs);

            // Insert Multiple Catatan and DetailCatatan
            $catatanRecords = [];
            $detailCatatanRecords = [];

            foreach ($request->catatan as $catatanData) {
                $catatan = Catatan::create([
                    'id_simulation' => $simulation->id,
                    'judul' => $catatanData['judul'],
                ]);

                $catatanRecords[] = $catatan; // Tambahkan ini agar masuk ke response!

                foreach ($catatanData['detailCatatan'] as $detail) {
                    $detailCatatanRecords[] = [
                        'id_catatan' => $catatan->id,
                        'teks' => $detail['teks'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                LoggerService::logAction($this->userData, $catatan, 'create', null, $catatan->toArray());
            }

            DetailCatatan::insert($detailCatatanRecords);

            LoggerService::logAction($this->userData, $simulation, 'create', null, $simulation->toArray());
            LoggerService::logAction($this->userData, $offer, 'create', null, $offer->toArray());

            DB::commit();

            return response()->json([
                'data' => [
                    'simulation' => $simulation,
                    'offer' => $offer,
                    'costs' => $costs,
                    'catatan' => $catatanRecords,
                    'detailCatatan' => $detailCatatanRecords,
                ],
                'message' => $this->messageCreate,
                'success' => true,
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => $this->messageFail,
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $rules = [
                'product_id' => 'required|exists:' . Product::class . ',id',
                'name' => 'required|unique:simulation,name,' . $id,
                'kurs' => 'required|numeric',
                'expected_margin' => 'required|numeric',
                'id_dmo' => 'required|exists:' . Dmo::class . ',id',
                'offer.buyer_name' => 'required|string',
                'offer.price' => 'required|numeric',
                'offer.volume' => 'required|numeric',

                // Costs Validation
                'costs' => 'required|array|min:1',
                'costs.*.id_master_cost' => 'required|exists:master_costs,id',
                'costs.*.value' => 'required|numeric',
                'costs.*.id_utilisasi' => 'required|exists:utilisasi,id',

                // Catatan Validation
                'catatan' => 'required|array|min:1',
                'catatan.*.judul' => 'required|string',

                // DetailCatatan Validation
                'catatan.*.detailCatatan' => 'required|array|min:1',
                'catatan.*.detailCatatan.*.teks' => 'required|string',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'success' => false,
                ], 400);
            }

            // Find existing Simulation
            $simulation = Simulation::findOrFail($id);

            // Update Simulation
            $simulation->update([
                'product_id' => $request->product_id,
                'name' => $request->name,
                'kurs' => $request->kurs,
                'expected_margin' => $request->expected_margin,
                'id_dmo' => $request->id_dmo,
            ]);

            // Update Offer
            $simulation->offer()->update([
                'buyer_name' => $request->offer['buyer_name'],
                'price' => $request->offer['price'],
                'volume' => $request->offer['volume'],
            ]);

            // Delete existing Costs
            $simulation->cost()->delete();

            // Insert new Costs
            $costs = [];
            foreach ($request->costs as $costData) {
                $costs[] = [
                    'id_master_cost' => $costData['id_master_cost'],
                    'value' => $costData['value'],
                    'id_utilisasi' => $costData['id_utilisasi'],
                    'id_simulation' => $simulation->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            Cost::insert($costs);

            // Delete existing Catatan and DetailCatatan
            $simulation->catatan()->each(function ($catatan) {
                $catatan->detailCatatan()->delete();
                $catatan->delete();
            });

            // Insert new Catatan and DetailCatatan
            $catatanRecords = [];
            $detailCatatanRecords = [];
            foreach ($request->catatan as $catatanData) {
                $catatan = Catatan::create([
                    'id_simulation' => $simulation->id,
                    'judul' => $catatanData['judul'],
                ]);
                $catatanRecords[] = $catatan;

                foreach ($catatanData['detailCatatan'] as $detail) {
                    $detailCatatanRecords[] = [
                        'id_catatan' => $catatan->id,
                        'teks' => $detail['teks'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            DetailCatatan::insert($detailCatatanRecords);

            LoggerService::logAction($this->userData, $simulation, 'update', null, $simulation->toArray());

            DB::commit();

            return response()->json([
                'data' => [
                    'simulation' => $simulation,
                    'offer' => $simulation->offer,
                    'costs' => $costs,
                    'catatan' => $catatanRecords,
                    'detailCatatan' => $detailCatatanRecords,
                ],
                'message' => $this->messageUpdate,
                'success' => true,
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Failed to update simulation',
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function calculate(Request $request)
    {
        try {
            $rules = [
                'kurs' => 'required|numeric',
                'expected_margin' => 'required|numeric',
                'id_dmo' => 'required|exists:' . Dmo::class . ',id',
                'offer.price' => 'required|numeric',
                'offer.volume' => 'required|numeric',

                'costs' => 'required|array|min:1',
                'costs.*.id_master_cost' => 'required|exists:master_costs,id',
                'costs.*.value' => 'required|numeric',
                'costs.*.id_utilisasi' => 'required|exists:utilisasi,id',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'success' => false,
                ], 400);
            }

            $kurs = $request->kurs;
            $expected_margin = $request->expected_margin;
            $dmo = Dmo::findOrFail($request->id_dmo);
            $pengaliList = Pengali::orderBy('name', 'asc')->get();
            $buyerPrice = $request->offer['price'];
            $buyerVolume = $request->offer['volume'];
            $utilities = [];

            foreach ($request->costs as $cost) {
                $masterCost = MasterCost::find($cost['id_master_cost']);
                $utilization = Utilisasi::find($cost['id_utilisasi']);

                if (!$masterCost || !$utilization) {
                    continue;
                }

                $utilityName = $utilization->name;
                $costValue = $cost['value'];

                if (!isset($utilities[$utilityName])) {
                    $utilities[$utilityName] = [
                        'name' => $utilityName,
                        'total' => 0,
                        'marginContribute' => 0,
                        'proportionContribute' => 0,
                        'dmoContribute' => 0,
                        'cost' => []
                    ];
                }

                $utilities[$utilityName]['total'] += $costValue;
                if ($masterCost->contribute_to_margin) {
                    $utilities[$utilityName]['marginContribute'] += $costValue;
                }

                if ($masterCost->contribute_to_proportion) {
                    $utilities[$utilityName]['proportionContribute'] += $costValue;
                }

                if ($masterCost->contribute_to_dmo) {
                    $utilities[$utilityName]['dmoContribute'] += $costValue;
                }
            }

            foreach ($request->costs as $cost) {
                $masterCost = MasterCost::find($cost['id_master_cost']);
                $utilization = Utilisasi::find($cost['id_utilisasi']);

                if (!$masterCost || !$utilization) {
                    continue;
                }

                $utilityName = $utilization->name;
                $costValue = $cost['value'];
                $proportionContribute = $utilities[$utilityName]['proportionContribute'];

                $proportionPercent = ($masterCost->contribute_to_proportion && $proportionContribute > 0)
                    ? ($costValue / $proportionContribute) * 100
                    : 0;

                $utilities[$utilityName]['cost'][] = [
                    'name' => $masterCost->name,
                    'value' => $costValue,
                    'usd' => ($costValue * 1000) / $kurs,
                    'proportion' => $proportionPercent !== 0 ? $proportionPercent : 0
                ];
            }

            foreach ($utilities as $utilityName => &$utility) {
                $utility['marginValue'] = ($expected_margin / 100) * $utility['marginContribute'];
                $utility['marginPercent'] = ($utility['total'] > 0)
                    ? ($utility['marginValue'] / $utility['marginContribute']) * 100 : 0;

                $fobIdr = $utility['proportionContribute'] + $utility['marginValue'];
                $fobUsd = ($fobIdr / $kurs) * 1000;
                $cpoPlusFob = $fobUsd - ($utility['cost'][0]['usd'] ?? 0);
                $cpoPlusLoco = $cpoPlusFob - ($utility['cost'][2]['usd'] ?? 0);

                $utility['tanpaDmo'] = [
                    [
                        'name' => 'FOB',
                        'idr' => $fobIdr,
                        'usd' => $fobUsd,
                        'cpoPlus' => $cpoPlusFob
                    ],
                    [
                        'name' => 'LOCO',
                        'idr' => null,
                        'usd' => null,
                        'cpoPlus' => $cpoPlusLoco
                    ]
                ];

                $biayaDmoKerugianIdr = $utility['dmoContribute'] - $dmo->value;
                $biayaDmoKerugianUsd = ($biayaDmoKerugianIdr / $kurs) * 1000;
                $biayaDmoKerugianProportion = ($dmo->value > 0)
                    ? round(($biayaDmoKerugianIdr / $dmo->value) * 100, 2) : 0;

                $utility['biayaDmoKerugian'] = [
                    'idr' => $biayaDmoKerugianIdr,
                    'usd' => $biayaDmoKerugianUsd,
                    'proportion' => $biayaDmoKerugianProportion
                ];

                $utility['biayaDmoDenganPengali'] = [];
                $utility['denganDmo'] = [];
                foreach ($pengaliList as $pengali) {
                    if ($pengali->value != 0) {
                        $biayaIdr = $biayaDmoKerugianIdr / $pengali->value;
                        $biayaUsd = ($biayaIdr / $kurs) * 1000;

                        $utility['biayaDmoDenganPengali'][] = [
                            'name' => $pengali->name,
                            'idr' => $biayaIdr,
                            'usd' => $biayaUsd,
                        ];

                        $utility['denganDmo'][] = [
                            'name' => $pengali->name,
                            'idr' => $fobIdr + $biayaIdr,
                            'usd' => $fobUsd + $biayaUsd,
                            'idrCpoPlus' => $fobIdr + $biayaIdr - ($utility['cost'][0]['value'] ?? 0),
                            'usdCpoPlus' => $fobUsd + $biayaUsd - ($utility['cost'][0]['usd'] ?? 0),
                        ];
                    }
                }
                usort($utility['biayaDmoDenganPengali'], fn($a, $b) => $b['idr'] <=> $a['idr']);
                usort($utility['denganDmo'], fn($a, $b) => $b['idr'] <=> $a['idr']);


                $utility['rekomHargaJualTanpaDmo'] = $utility['tanpaDmo'][0]['usd'] ?? 0;

                $utility['rekomHargaJualDenganDmo'] = array_map(fn($dmo) => [
                    'name' => $dmo['name'],
                    'value' => $dmo['usd']
                ], $utility['denganDmo']);

                $utility['potensiLabaRugiTanpaDmo'] = $buyerVolume * ($buyerPrice - $utility['rekomHargaJualTanpaDmo']);

                $utility['potensiLabaRugiDenganDmo'] = array_map(fn($dmo) => [
                    'name' => $dmo['name'],
                    'value' => $buyerVolume * ($buyerPrice - $dmo['value'])
                ], $utility['rekomHargaJualDenganDmo']);
            }

            return response()->json([
                'data' => ['utilisasi' => array_values($utilities)],
                'message' => $this->messageCreate,
                'success' => true,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $this->messageFail,
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }
}
