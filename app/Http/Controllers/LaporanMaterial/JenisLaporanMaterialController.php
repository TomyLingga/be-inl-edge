<?php

namespace App\Http\Controllers\LaporanMaterial;

use App\Http\Controllers\Controller;
use App\Models\Master\JenisLaporanMaterial;
use App\Services\LoggerService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class JenisLaporanMaterialController extends Controller
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
            $data = JenisLaporanMaterial::with('items')->get();

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
            $data = JenisLaporanMaterial::with('items')->findOrFail($id);

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
                'name' => 'required|unique:jenis_laporan_produksi,name',
                'condition_olah' => 'required|in:sum,use_higher,use_lower,difference',
                'items' => 'required|array',
                'items.*.name' => 'required',
                'items.*.kategori' => 'required|in:incoming,outgoing,proportion,others',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'success' => false,
                ], 400);
            }

            $validatedData = $validator->validated();

            $jenisLaporanProduksi = JenisLaporanMaterial::create([
                'name' => $validatedData['name'],
                'condition_olah' => $validatedData['condition_olah'],
            ]);

            $itemsData = array_map(function ($itemData) use ($jenisLaporanProduksi) {
                return [
                    'name' => $itemData['name'],
                    'kategori' => $itemData['kategori'],
                    'jenis_laporan_produksi_id' => $jenisLaporanProduksi->id,
                ];
            }, $validatedData['items']);

            $jenisLaporanProduksi->items()->createMany($itemsData);

            LoggerService::logAction($this->userData, $jenisLaporanProduksi, 'create', null, $jenisLaporanProduksi->toArray());

            DB::commit();

            return response()->json([
                'data' => $jenisLaporanProduksi,
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
                'name' => 'required|unique:jenis_laporan_produksi,name,' . $id,
                'condition_olah' => 'required|in:sum,use_higher,use_lower,difference',
                'items' => 'required|array',
                'items.*.id' => 'nullable|exists:item_produksi,id',
                'items.*.name' => 'required',
                'items.*.kategori' => 'required|in:incoming,outgoing,proportion,others',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'success' => false,
                ], 400);
            }

            $validatedData = $validator->validated();

            $jenisLaporanProduksi = JenisLaporanMaterial::findOrFail($id);

            $jenisLaporanProduksi->update([
                'name' => $validatedData['name'],
                'condition_olah' => $validatedData['condition_olah'],
            ]);

            $providedItemIds = collect($validatedData['items'])
                ->filter(fn($item) => isset($item['id']))
                ->pluck('id')
                ->toArray();

            $existingItemIds = $jenisLaporanProduksi->items->pluck('id')->toArray();

            $idsToDelete = array_diff($existingItemIds, $providedItemIds);

            if (!empty($idsToDelete)) {
                $jenisLaporanProduksi->items()->whereIn('id', $idsToDelete)->delete();
            }

            foreach ($validatedData['items'] as $itemData) {
                if (isset($itemData['id'])) {
                    $item = $jenisLaporanProduksi->items()->find($itemData['id']);
                    if ($item) {
                        $item->update([
                            'name' => $itemData['name'],
                            'kategori' => $itemData['kategori'],
                        ]);
                    }
                } else {
                    $jenisLaporanProduksi->itemProduks()->create([
                        'name' => $itemData['name'],
                        'kategori' => $itemData['kategori'],
                    ]);
                }
            }

            LoggerService::logAction($this->userData, $jenisLaporanProduksi, 'update', null, $jenisLaporanProduksi->toArray());

            DB::commit();

            return response()->json([
                'data' => $jenisLaporanProduksi,
                'message' => $this->messageUpdate,
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
}
