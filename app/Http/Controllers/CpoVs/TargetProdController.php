<?php

namespace App\Http\Controllers\CpoVs;

use App\Http\Controllers\BebanProdViewer;
use App\Http\Controllers\Controller;
use App\Models\Master\Pmg;
use App\Models\Master\TargetProduksiUraian;
use App\Models\Target\TargetProduksi;
use App\Services\LoggerService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TargetProdController extends Controller
{
    protected $bebanProdViewer;

    public function __construct(BebanProdViewer $bebanProdViewer)
    {
        parent::__construct();

        $this->bebanProdViewer = $bebanProdViewer;
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
            $data = TargetProduksi::with('uraian', 'pmg')->get();

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
            $data = TargetProduksi::with('uraian', 'pmg')->findOrFail($id);

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
                'uraian_id' => 'required|exists:' . TargetProduksiUraian::class . ',id',
                'pmg_id' => 'required|exists:' . Pmg::class . ',id',
                'tanggal' => 'required|date',
                'value' => 'required|numeric'
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'success' => false,
                ], 400);
            }

            $existingEntry = TargetProduksi::where('uraian_id', $request->uraian_id)
                                        ->where('pmg_id', $request->pmg_id)
                                        ->whereYear('tanggal', date('Y', strtotime($request->tanggal)))
                                        ->whereMonth('tanggal', date('m', strtotime($request->tanggal)))
                                        ->first();

            if ($existingEntry) {
                return response()->json([
                    'message' => 'Entry already exists for this uraian and month',
                    'success' => false,
                ], 400);
            }

            $data = TargetProduksi::create($request->all());

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
                'uraian_id' => 'required|exists:' . TargetProduksiUraian::class . ',id',
                'pmg_id' => 'required|exists:' . Pmg::class . ',id',
                'tanggal' => 'required|date',
                'value' => 'required|numeric'
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'success' => false,
                ], 400);
            }

            $data = TargetProduksi::findOrFail($id);
            $oldData = $data->toArray();

            $existingEntry = TargetProduksi::where('uraian_id', $request->uraian_id)
                        ->where('pmg_id', $request->pmg_id)
                        ->whereYear('tanggal', date('Y', strtotime($request->tanggal)))
                        ->whereMonth('tanggal', date('m', strtotime($request->tanggal)))
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

            $data = $this->bebanProdViewer->indexPeriodTargetProd($tanggalAwal, $tanggalAkhir, $idPmg);

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
