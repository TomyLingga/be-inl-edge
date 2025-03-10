<?php

namespace App\Http\Controllers\LaporanProduksi;

use App\Http\Controllers\Controller;
use App\Models\Master\JenisLaporanProduksi;
use App\Services\LoggerService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class JenisLaporanProduksiController extends Controller
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
            $data = JenisLaporanProduksi::with('itemProduksi')->orderBy('name', 'asc')
            ->get();

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
            $data = JenisLaporanProduksi::with('itemProduksi')->findOrFail($id);

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
                'name' => 'required|unique:jenis_laporan_produksi,name',
                'condition_olah' => 'required|in:sum,use_higher,use_lower,difference',
                'items' => 'required|array',
                'items.*.name' => 'required',
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

            $jenisLaporanProduksi = JenisLaporanProduksi::create([
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

            $jenisLaporanProduksi->itemProduksi()->createMany($itemsData);

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

            // Find the JenisLaporanProduksi to update
            $jenisLaporanProduksi = JenisLaporanProduksi::findOrFail($id);

            // Update the main record
            $jenisLaporanProduksi->update([
                'name' => $validatedData['name'],
                'condition_olah' => $validatedData['condition_olah'],
            ]);

            // Get current item IDs from the request
            $providedItemIds = collect($validatedData['items'])
                ->filter(fn($item) => isset($item['id']))
                ->pluck('id')
                ->toArray();

            // Get all existing item IDs in the database for this record
            $existingItemIds = $jenisLaporanProduksi->itemProduksi->pluck('id')->toArray();

            // Find IDs to delete (existing but not in the request)
            $idsToDelete = array_diff($existingItemIds, $providedItemIds);

            // Delete the items that are no longer included
            if (!empty($idsToDelete)) {
                $jenisLaporanProduksi->itemProduksi()->whereIn('id', $idsToDelete)->delete();
            }

            // Process items
            foreach ($validatedData['items'] as $itemData) {
                if (isset($itemData['id'])) {
                    // Update existing item
                    $item = $jenisLaporanProduksi->itemProduksi()->find($itemData['id']);
                    if ($item) {
                        $item->update([
                            'name' => $itemData['name'],
                            'kategori' => $itemData['kategori'],
                        ]);
                    }
                } else {
                    // Add new item
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
