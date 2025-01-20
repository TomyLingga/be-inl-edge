<?php

namespace App\Http\Controllers\Penjualan;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PenjualanViewer;
use App\Models\Master\Product;
use App\Models\Penjualan\TargetPenjualan;
use App\Models\Penjualan\TargetPenjualanUraian;
use App\Services\LoggerService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TargetPenjualanController extends Controller
{
    protected $penjualanViewer;

    public function __construct(PenjualanViewer $penjualanViewer)
    {
        parent::__construct();

        $this->penjualanViewer = $penjualanViewer;
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
            $data = TargetPenjualan::with('uraian', 'product')->get();

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
            $data = TargetPenjualan::with('uraian', 'product')->findOrFail($id);

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
                'uraian_id' => 'required|exists:' . TargetPenjualanUraian::class . ',id',
                'product_id' => 'required|exists:' . Product::class . ',id',
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

            $tanggal = Carbon::parse($request->tanggal);
            $year = $tanggal->year;
            $month = $tanggal->month;

            $existingEntry = TargetPenjualan::where('uraian_id', $request->uraian_id)
                                        ->where('product_id', $request->product_id)
                                        ->whereYear('tanggal', $year)
                                        ->whereMonth('tanggal', $month)
                                        ->first();

            if ($existingEntry) {
                return response()->json([
                    'message' => 'Entry already exists for this uraian, jenis and month',
                    'success' => false,
                ], 400);
            }

            $data = TargetPenjualan::create($request->all());

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
                'uraian_id' => 'required|exists:' . TargetPenjualanUraian::class . ',id',
                'product_id' => 'required|exists:' . Product::class . ',id',
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

            $data = TargetPenjualan::findOrFail($id);
            $oldData = $data->toArray();

            $tanggal = Carbon::parse($request->tanggal);
            $year = $tanggal->year;
            $month = $tanggal->month;

            $existingEntry = TargetPenjualan::where('uraian_id', $request->uraian_id)
                        ->where('product_id', $request->product_id)
                        ->whereYear('tanggal', $year)
                        ->whereMonth('tanggal', $month)
                        ->where('id', '!=', $id)
                        ->first();

            if ($existingEntry) {
                return response()->json([
                    'message' => 'An entry already exists for this uraian, jenis and month',
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

        try {

            $data = $this->penjualanViewer->indexPeriodTargetPenjualan($tanggalAwal, $tanggalAkhir);

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
