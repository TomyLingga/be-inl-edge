<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StockViewer;
use App\Models\Master\Product;
use App\Models\Master\ProductStorage;
use App\Models\Stock\StockBulk;
use App\Models\Stock\StockCpo;
use App\Services\LoggerService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StockBulkController extends Controller
{
    protected $stockViewer;

    public function __construct(StockViewer $stockViewer)
    {
        parent::__construct();

        $this->stockViewer = $stockViewer;
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
            $data = StockBulk::with(['tanki.lokasi', 'product'])->get();

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
            $data = StockBulk::with(['tanki.lokasi', 'product'])->findOrFail($id);

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
                'tanki_id' => 'required|exists:' . ProductStorage::class . ',id',
                'id_bulky' => 'required|exists:' . Product::class . ',id',
                'tanggal' => 'required|date',
                'qty' => 'required|numeric',
                'umur' => 'required|numeric',
                'remarks' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'success' => false,
                ], 400);
            }

            $existingCpo = StockCpo::where('tanki_id', $request->tanki_id)
                                        ->whereDate('tanggal', $request->tanggal)
                                        ->first();

            if ($existingCpo) {
                return response()->json([
                    'message' => 'tank ini sudah diisi CPO pada tanggal ini',
                    'success' => false,
                ], 400);
            }

            $existingEntry = StockBulk::where('tanki_id', $request->tanki_id)
                                        ->whereDate('tanggal', $request->tanggal)
                                        ->first();

            if ($existingEntry) {
                return response()->json([
                    'message' => 'tank ini sudah berisi produk lain pada tanggal ini',
                    'success' => false,
                ], 400);
            }

            $data = StockBulk::create($request->all());

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
                'tanki_id' => 'required|exists:' . ProductStorage::class . ',id',
                'id_bulky' => 'required|exists:' . Product::class . ',id',
                'tanggal' => 'required|date',
                'qty' => 'required|numeric',
                'umur' => 'required|numeric',
                'remarks' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'success' => false,
                ], 400);
            }

            $data = StockBulk::findOrFail($id);
            $oldData = $data->toArray();

            $existingCpo = StockCpo::where('tanki_id', $request->tanki_id)
                                        ->whereDate('tanggal', $request->tanggal)
                                        ->first();

            if ($existingCpo) {
                return response()->json([
                    'message' => 'tank ini sudah diisi CPO pada tanggal ini',
                    'success' => false,
                ], 400);
            }

            $existingEntry = StockBulk::where('tanki_id', $request->tanki_id)
                        ->whereDate('tanggal', $request->tanggal)
                        ->where('id', '!=', $id)
                        ->first();

            if ($existingEntry) {
                return response()->json([
                    'message' => 'tank ini sudah berisi produk lain pada tanggal ini',
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

            $data = $this->stockViewer->indexPeriodStockBulk($tanggalAwal, $tanggalAkhir);

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
