<?php

namespace App\Http\Controllers\Packaging;

use App\Http\Controllers\Controller;
use App\Models\Master\Product;
use App\Models\Packaging\JenisLaporanPackaging;
use App\Services\LoggerService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class JenisLaporanPackagingController extends Controller
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
            $data = JenisLaporanPackaging::with(['itemPackaging.productHasil'])->get();

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
            $data = JenisLaporanPackaging::with(['itemPackaging.productHasil'])->findOrFail($id);

            $data->history = $this->formatLogsForMultiple($data->logs);
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
                'name' => 'required|unique:jenis_laporan_packaging,name',
                'condition_olah' => 'required|in:sum,use_higher,use_lower,difference',
                'items' => 'required|array',
                'items.*.product_id' => 'required_if:items.*.kategori,produk_hasil|nullable|exists:product,id',
                'items.*.name' => 'required_unless:items.*.kategori,produk_hasil',
                'items.*.kategori' => 'required|in:bahan_olah,produk_hasil,others',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'success' => false,
                ], 400);
            }

            $validatedData = $validator->validated();

            $jenisLaporanPackaging = JenisLaporanPackaging::create([
                'name' => $validatedData['name'],
                'condition_olah' => $validatedData['condition_olah'],
            ]);

            $itemsData = array_map(function ($itemData) use ($jenisLaporanPackaging) {
                return [
                    'name' => $itemData['kategori'] === 'produk_hasil'
                        ? Product::find($itemData['product_id'])->name
                        : $itemData['name'],
                    'kategori' => $itemData['kategori'],
                    'product_id' => $itemData['kategori'] === 'produk_hasil' ? $itemData['product_id'] : null,
                    'jenis_laporan_id' => $jenisLaporanPackaging->id,
                ];
            }, $validatedData['items']);

            $jenisLaporanPackaging->itemPackaging()->createMany($itemsData);

            LoggerService::logAction($this->userData, $jenisLaporanPackaging, 'create', null, $jenisLaporanPackaging->toArray());

            DB::commit();

            return response()->json([
                'data' => $jenisLaporanPackaging,
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
                'name' => 'required|unique:jenis_laporan_packaging,name,' . $id,
                'condition_olah' => 'required|in:sum,use_higher,use_lower,difference',
                'items' => 'required|array',
                'items.*.id' => 'nullable|exists:item_packaging,id',
                'items.*.product_id' => 'required_if:items.*.kategori,produk_hasil|nullable|exists:product,id',
                'items.*.name' => 'required_unless:items.*.kategori,produk_hasil',
                'items.*.kategori' => 'required|in:bahan_olah,produk_hasil,others',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                    'success' => false,
                ], 400);
            }

            $validatedData = $validator->validated();

            $jenisLaporanPackaging = JenisLaporanPackaging::findOrFail($id);

            // Update main entity
            $jenisLaporanPackaging->update([
                'name' => $validatedData['name'],
                'condition_olah' => $validatedData['condition_olah'],
            ]);

            $providedItemIds = collect($validatedData['items'])
                ->filter(fn($item) => isset($item['id']))
                ->pluck('id')
                ->toArray();

            $existingItemIds = $jenisLaporanPackaging->itemPackaging->pluck('id')->toArray();

            $idsToDelete = array_diff($existingItemIds, $providedItemIds);

            // Delete items not provided in the update request
            if (!empty($idsToDelete)) {
                $jenisLaporanPackaging->itemPackaging()->whereIn('id', $idsToDelete)->delete();
            }

            // Process items
            foreach ($validatedData['items'] as $itemData) {
                if (isset($itemData['id'])) {
                    // Update existing item
                    $item = $jenisLaporanPackaging->itemPackaging()->find($itemData['id']);
                    if ($item) {
                        $item->update([
                            'name' => $itemData['kategori'] === 'produk_hasil'
                                ? Product::find($itemData['product_id'])->name
                                : $itemData['name'],
                            'kategori' => $itemData['kategori'],
                            'product_id' => $itemData['kategori'] === 'produk_hasil' ? $itemData['product_id'] : null,
                        ]);
                    }
                } else {
                    // Add new item
                    $jenisLaporanPackaging->itemPackaging()->create([
                        'name' => $itemData['kategori'] === 'produk_hasil'
                            ? Product::find($itemData['product_id'])->name
                            : $itemData['name'],
                        'kategori' => $itemData['kategori'],
                        'product_id' => $itemData['kategori'] === 'produk_hasil' ? $itemData['product_id'] : null,
                        'jenis_laporan_id' => $jenisLaporanPackaging->id,
                    ]);
                }
            }

            LoggerService::logAction($this->userData, $jenisLaporanPackaging, 'update', null, $jenisLaporanPackaging->toArray());

            DB::commit();

            return response()->json([
                'data' => $jenisLaporanPackaging,
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
