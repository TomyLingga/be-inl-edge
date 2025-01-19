<?php

namespace App\Http\Controllers\LevyReuters;

use App\Http\Controllers\Controller;
use App\Models\Kurs\MataUang;
use App\Models\LevyReuter\MarketReuters;
use App\Models\Master\Product;
use App\Services\LoggerService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MarketReutersController extends Controller
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
            $data = MarketReuters::with('product', 'mataUang')->get();

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
            $data = MarketReuters::with('product', 'mataUang')->findOrFail($id);

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
                'id_bulky' => 'required|exists:' . Product::class . ',id',
                'id_mata_uang' => 'required|exists:' . MataUang::class . ',id',
                'tanggal' => 'required|date',
                'nilai' => 'required|numeric'
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'success' => false,
                ], 400);
            }

            $existingEntry = MarketReuters::where('id_bulky', $request->id_bulky)
                                        ->where('id_mata_uang', $request->id_mata_uang)
                                        ->whereDate('tanggal', $request->tanggal)
                                        ->first();

            if ($existingEntry) {
                return response()->json([
                    'message' => 'Entry already exists for this uraian and date',
                    'success' => false,
                ], 400);
            }

            $data = MarketReuters::create($request->all());

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
                'id_bulky' => 'required|exists:' . Product::class . ',id',
                'id_mata_uang' => 'required|exists:' . MataUang::class . ',id',
                'tanggal' => 'required|date',
                'nilai' => 'required|numeric'
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'success' => false,
                ], 400);
            }

            $data = MarketReuters::findOrFail($id);
            $oldData = $data->toArray();

            $existingEntry = MarketReuters::where('id_bulky', $request->id_bulky)
                        ->where('id_mata_uang', $request->id_mata_uang)
                        ->whereDate('tanggal', $request->tanggal)
                        ->where('id', '!=', $id)
                        ->first();

            if ($existingEntry) {
                return response()->json([
                    'message' => 'An entry already exists for this uraian and date',
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
}
