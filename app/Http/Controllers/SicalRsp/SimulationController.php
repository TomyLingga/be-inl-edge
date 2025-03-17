<?php

namespace App\Http\Controllers\SicalRsp;

use App\Http\Controllers\Controller;
use App\Models\Master\Product;
use App\Models\SicalRsp\Catatan;
use App\Models\SicalRsp\Cost;
use App\Models\SicalRsp\DetailCatatan;
use App\Models\SicalRsp\Dmo;
use App\Models\SicalRsp\Offer;
use App\Models\SicalRsp\Simulation;
use App\Services\LoggerService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SimulationController extends Controller
{
    // protected $laporanProdViewer;

    // public function __construct(LaporanProduksiViewer $laporanProdViewer)
    // {
    //     parent::__construct();

    //     $this->laporanProdViewer = $laporanProdViewer;
    // }

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
            $simulation->costs()->delete();

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
                'message' => 'Simulation updated successfully',
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

}
