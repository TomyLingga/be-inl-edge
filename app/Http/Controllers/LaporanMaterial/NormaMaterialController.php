<?php

namespace App\Http\Controllers\LaporanMaterial;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LaporanMaterialViewer;
use App\Models\LaporanMaterial\NormaMaterial;
use App\Models\Master\ItemMaterial;
use App\Models\Master\Pmg;
use App\Services\LoggerService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class NormaMaterialController extends Controller
{
    protected $laporanMaterialViewer;

    public function __construct(LaporanMaterialViewer $laporanMaterialViewer)
    {
        parent::__construct();

        $this->laporanMaterialViewer = $laporanMaterialViewer;
    }

    private $messageFail = 'Something went wrong';
    private $messageMissing = 'Data not found in record';
    private $messageAll = 'Success to Fetch All Datas';
    private $messageSuccess = 'Success to Fetch Data';
    private $messageCreate = 'Success to Create Data';
    private $messageUpdate = 'Success to Update Data';

    public function index()
    {
        try {
            $data = NormaMaterial::with('itemMaterial')->get();

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
            $data = NormaMaterial::with('itemMaterial')->findOrFail($id);

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
                'item_material_id' => 'required|exists:' . ItemMaterial::class . ',id',
                'pmg_id' => 'required|exists:' . Pmg::class . ',id',
                'tanggal' => 'required|date',
                'satuan' => 'required',
                'qty' => 'required|numeric',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'success' => false,
                ], 400);
            }

            $tanggal = Carbon::parse($request->tanggal);
            $year = $tanggal->year;
            $month = $tanggal->month;

            $existingEntry = NormaMaterial::where('item_material_id', $request->item_material_id)
                ->where('pmg_id', $request->pmg_id)
                ->whereYear('tanggal', $year)
                ->whereMonth('tanggal', $month)
                ->first();

            if ($existingEntry) {
                return response()->json([
                    'message' => 'Entry already exists for this uraian and month',
                    'success' => false,
                ], 400);
            }

            $data = NormaMaterial::create($request->all());

            LoggerService::logAction($this->userData, $data, 'create', null, $data->toArray());

            DB::commit();

            return response()->json([
                'data' => $data,
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
                'item_material_id' => 'required|exists:' . ItemMaterial::class . ',id',
                'pmg_id' => 'required|exists:' . Pmg::class . ',id',
                'tanggal' => 'required|date',
                'qty' => 'required|numeric'
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'success' => false,
                ], 400);
            }

            $data = NormaMaterial::findOrFail($id);
            $oldData = $data->toArray();

            $tanggal = Carbon::parse($request->tanggal);
            $year = $tanggal->year;
            $month = $tanggal->month;

            $existingEntry = NormaMaterial::where('item_material_id', $request->item_material_id)
                        ->where('pmg_id', $request->pmg_id)
                        ->whereYear('tanggal', $year)
                        ->whereMonth('tanggal', $month)
                        ->where('id', '!=', $id)
                        ->first();

            if ($existingEntry) {
                return response()->json([
                    'message' => 'An entry already exists for this uraian and month',
                    'success' => false,
                ], 400);
            }

            $data->update($request->all());

            LoggerService::logAction($this->userData, $data, 'update', $oldData, $data->toArray());

            DB::commit();

            return response()->json([
                'data' => $data,
                'message' => $this->messageUpdate,
                'success' => true,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollback();
            return response()->json([
                'message' => $this->messageFail,
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function indexPeriod(Request $request)
    {
        $tanggalAwal = $request->tanggalAwal;
        $tanggalAkhir = $request->tanggalAkhir;
        $idPmg = $request->idPmg;

        try {

            $data = $this->laporanMaterialViewer->indexPeriodNormaMaterial($tanggalAwal, $tanggalAkhir, $idPmg);

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
}
