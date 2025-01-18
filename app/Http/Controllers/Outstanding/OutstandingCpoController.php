<?php

namespace App\Http\Controllers\Outstanding;

use App\Http\Controllers\Controller;
use App\Models\OutstandingCpo\OutstandingCpo;
use App\Models\Partner\Supplier;
use App\Services\LoggerService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OutstandingCpoController extends Controller
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
            $data = OutstandingCpo::with('supplier')->get();

            $data = $data->map(function ($item) {
                $item->value = $item->qty * $item->harga;
                return $item;
            });
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
            $data = OutstandingCpo::with('supplier')->findOrFail($id);

            $data->value = $data->qty * $data->harga;

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
                'kontrak' => 'required',
                'supplier_id' => 'required|exists:' . Supplier::class . ',id',
                'qty' => 'required|numeric',
                'harga' => 'required|numeric',
                'status' => 'required|boolean'

            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    // 'code' => 400,
                    'success' => false,
                ], 400);
            }


            $data = OutstandingCpo::create($request->all());

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
                'kontrak' => 'required',
                'supplier_id' => 'required|exists:' . Supplier::class . ',id',
                'qty' => 'required|numeric',
                'harga' => 'required|numeric',
                'status' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    // 'code' => 400,
                    'success' => false
                ], 400);
            }
            $data = OutstandingCpo::findOrFail($id);

            $dataToUpdate = [
                'kontrak' => $request->filled('kontrak') ? $request->kontrak : $data->kontrak,
                'supplier_id' => $request->filled('supplier_id') ? $request->supplier_id : $data->supplier_id,
                'qty' => $request->filled('qty') ? $request->qty : $data->qty,
                'harga' => $request->filled('harga') ? $request->harga : $data->harga,
                'status' => $request->filled('status') ? $request->status : $data->status,
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

    public function indexPeriod()
    {
        try {

            $data = OutstandingCpo::with('supplier')->where('status', 1)->get();

            $data = $data->map(function ($item) {
                $item->value = $item->qty * $item->harga;
                return $item;
            });

            $totalQty = $data->sum('qty'); // Sum the qty column
            $totalValue = $data->sum('value'); // Sum the calculated value column

            // Return response with totals
            return response()->json([
                'totalQty' => $totalQty,
                'totalValue' => $totalValue,
                'data' => $data,
                'message' => $this->messageUpdate,
                'success' => true
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
}
