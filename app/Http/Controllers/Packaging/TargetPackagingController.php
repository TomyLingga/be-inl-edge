<?php

namespace App\Http\Controllers\Packaging;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PackagingViewer;
use App\Models\Packaging\JenisLaporanPackaging;
use App\Models\Packaging\Packaging;
use App\Models\Packaging\TargetPackaging;
use App\Models\Packaging\TargetPackagingUraian;
use App\Services\LoggerService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TargetPackagingController extends Controller
{
    protected $packagingViewer;

    public function __construct(PackagingViewer $packagingViewer)
    {
        parent::__construct();

        $this->packagingViewer = $packagingViewer;
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
            $data = TargetPackaging::with('uraian', 'packaging')->get();

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
            $data = TargetPackaging::with('uraian', 'packaging')->findOrFail($id);

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
                'uraian_id' => 'required|exists:' . TargetPackagingUraian::class . ',id',
                'packaging_id' => 'required|exists:' . Packaging::class . ',id',
                'jenis_id' => 'required|exists:' . JenisLaporanPackaging::class . ',id',
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

            $tanggal = Carbon::parse($request->tanggal);
            $year = $tanggal->year;
            $month = $tanggal->month;

            $existingEntry = TargetPackaging::where('uraian_id', $request->uraian_id)
                                        ->where('jenis_id', $request->jenis_id)
                                        ->where('packaging_id', $request->packaging_id)
                                        ->whereYear('tanggal', $year)
                                        ->whereMonth('tanggal', $month)
                                        ->first();

            if ($existingEntry) {
                return response()->json([
                    'message' => 'Entry already exists for this uraian, jenis and month',
                    'success' => false,
                ], 400);
            }

            $data = TargetPackaging::create($request->all());

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
                'uraian_id' => 'required|exists:' . TargetPackagingUraian::class . ',id',
                'packaging_id' => 'required|exists:' . Packaging::class . ',id',
                'jenis_id' => 'required|exists:' . JenisLaporanPackaging::class . ',id',
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

            $data = TargetPackaging::findOrFail($id);
            $oldData = $data->toArray();

            $tanggal = Carbon::parse($request->tanggal);
            $year = $tanggal->year;
            $month = $tanggal->month;

            $existingEntry = TargetPackaging::where('uraian_id', $request->uraian_id)
                        ->where('jenis_id', $request->jenis_id)
                        ->where('packaging_id', $request->packaging_id)
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
        $idPackaging = $request->idPackaging;

        try {

            $data = $this->packagingViewer->indexPeriodTargetPackaging($tanggalAwal, $tanggalAkhir, $idPackaging);

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
