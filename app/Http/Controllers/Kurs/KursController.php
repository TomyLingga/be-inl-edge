<?php

namespace App\Http\Controllers\Kurs;

use App\Http\Controllers\Controller;
use App\Http\Controllers\CpoKpbnViewer;
use App\Models\Kurs\Kurs;
use App\Models\Kurs\MataUang;
use App\Services\LoggerService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class KursController extends Controller
{
    private $messageFail = 'Something went wrong';
    private $messageMissing = 'Data not found in record';
    private $messageAll = 'Success to Fetch All Datas';
    private $messageSuccess = 'Success to Fetch Data';
    private $messageCreate = 'Success to Create Data';
    private $messageUpdate = 'Success to Update Data';

    protected $cpoKpbnViewer;

    public function __construct(CpoKpbnViewer $cpoKpbnViewer)
    {
        parent::__construct();

        $this->cpoKpbnViewer = $cpoKpbnViewer;
    }

    public function index()
    {
        try {
            $data = Kurs::with('mataUang')->get();

            return $data->isEmpty()
                ? response()->json(['message' => $this->messageMissing], 401)
                : response()->json(['data' => $data, 'message' => $this->messageAll], 200);
        } catch (QueryException $e) {
            return response()->json([
                'message' => $this->messageFail,
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                // 'code' => 500,
                'success' => false,
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $data = Kurs::with('mataUang')->findOrFail($id);

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
                // 'code' => 500,
                'success' => false,
            ], 500);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'id_mata_uang' => 'required|exists:' . MataUang::class . ',id',
                'tanggal' => 'required|date',
                'value' => 'required|numeric'

            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    // 'code' => 400,
                    'success' => false,
                ], 400);
            }


            $existingEntry = Kurs::where('id_mata_uang', $request->id_mata_uang)
                                        ->where('tanggal', $request->tanggal)
                                        ->first();

            if ($existingEntry) {
                return response()->json([
                    'message' => 'Entry already exists for this currency and date',
                    'success' => false,
                ], 400);
            }

            $data = Kurs::create($request->all());

            LoggerService::logAction($this->userData, $data, 'create', null, $data->toArray());

            DB::commit();

            return response()->json([
                'data' => $data,
                'message' => $this->messageCreate,
                // 'code' => 200,
                'success' => true,
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => $this->messageFail,
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                // 'code' => 500,
                'success' => false,
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {

            $validator = Validator::make($request->all(), [
                'id_mata_uang' => 'required|exists:' . MataUang::class . ',id',
                'tanggal' => 'required|date',
                'value' => 'required|numeric'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    // 'code' => 400,
                    'success' => false
                ], 400);
            }
            $data = Kurs::findOrFail($id);

            $existingEntry = Kurs::where('id_mata_uang', $request->id_mata_uang)
                                ->where('tanggal', $request->tanggal)
                                ->where('id', '!=', $id)
                                ->first();

            if ($existingEntry) {
                return response()->json([
                    'message' => 'An entry already exists for this uraian and date',
                    'success' => false,
                ], 400);
            }

            $dataToUpdate = [
                'id_mata_uang' => $request->filled('id_mata_uang') ? $request->id_mata_uang : $data->id_mata_uang,
                'tanggal' => $request->filled('tanggal') ? $request->tanggal : $data->tanggal,
                'value' => $request->filled('value') ? $request->value : $data->value,
            ];

            $oldData = $data->toArray();
            $data->update($dataToUpdate);

            LoggerService::logAction($this->userData, $data, 'update', $oldData, $data->toArray());

            DB::commit();

            return response()->json([
                'data' => $data,
                'message' => $this->messageUpdate,
                // 'code' => 200,
                'success' => true
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => $this->messageFail,
                'err' => $e->getTrace()[0],
                'errMsg' => $e->getMessage(),
                // 'code' => 500,
                'success' => false
            ], 500);
        }
    }

    public function indexPeriod(Request $request)
    {
        $tanggalAwal = $request->tanggalAwal;
        $tanggalAkhir = $request->tanggalAkhir;
        $idMataUang = $request->idMataUang;

        try {

            $data = $this->cpoKpbnViewer->indexPeriodKurs($tanggalAwal, $tanggalAkhir, $idMataUang);

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
