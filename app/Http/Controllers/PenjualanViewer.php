<?php

namespace App\Http\Controllers;

use App\Models\Penjualan\LaporanPenjualan;
use App\Models\Penjualan\TargetPenjualan;
use Illuminate\Http\Request;

class PenjualanViewer extends Controller
{

    public function indexPeriodTargetPenjualan($tanggalAwal, $tanggalAkhir)
    {
        // Retrieve target penjualan data
        $data = TargetPenjualan::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
            ->with('uraian', 'product')
            ->get();

        if ($data->isEmpty()) {
            return null;
        }

        // Retrieve actual penjualan data
        $dataPenjualan = $this->indexPeriodPenjualan($tanggalAwal, $tanggalAkhir);

        // Ensure $dataPenjualan contains the expected structure
        $dataPenjualan = $dataPenjualan ?? ['bulk' => ['products' => [], 'totalQtyKategori' => 0], 'ritel' => ['products' => [], 'totalQtyKategori' => 0]];

        $groupedData = $data->groupBy('product_id')->map(function ($items) {
            $product = $items->first()->product;

            return [
                'idProduct' => $product->id,
                'name' => $product->name,
                'jenis' => $product->jenis,
                'target' => $items->groupBy('uraian_id')->map(function ($uraianItems) {
                    $uraian = $uraianItems->first()->uraian;

                    return [
                        'nama' => $uraian->nama,
                        'totalQtyTarget' => $uraianItems->sum('qty'),
                        'detail' => $uraianItems->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'qty' => $item->qty,
                                'tanggal' => $item->tanggal,
                                'created_at' => $item->created_at,
                                'updated_at' => $item->updated_at,
                            ];
                        }),
                    ];
                })->values(),
            ];
        })->values();

        // Filter data by kategori: bulk and ritel
        $groupedDataBulk = $groupedData->filter(fn($item) => $item['jenis'] === 'bulk')->values();
        $groupedDataRitel = $groupedData->filter(fn($item) => $item['jenis'] === 'ritel')->values();

        // Calculate totalQtyTargetKategori for bulk and ritel
        $totalQtyBulk = $groupedDataBulk->sum(fn($item) => $item['target']->sum('totalQtyTarget'));
        $totalQtyRitel = $groupedDataRitel->sum(fn($item) => $item['target']->sum('totalQtyTarget'));

        $totalQtyBulkPenjualan = $dataPenjualan['bulk']['totalQtyKategori'] ?? 0;
        $totalQtyRitelPenjualan = $dataPenjualan['ritel']['totalQtyKategori'] ?? 0;

        $percentageQtyToTargetBulk = $totalQtyBulk === 0 ? 0 : ($totalQtyBulkPenjualan / $totalQtyBulk) * 100;
        $percentageQtyToTargetRitel = $totalQtyRitel === 0 ? 0 : ($totalQtyRitelPenjualan / $totalQtyRitel) * 100;

        // Handle products mapping
        $mapProducts = function ($groupedData, $kategori) use ($dataPenjualan) {
            return $groupedData->map(function ($product) use ($dataPenjualan, $kategori) {
                // Check if products exist in dataPenjualan before accessing
                $productPenjualan = isset($dataPenjualan[$kategori]['products'])
                    ? collect($dataPenjualan[$kategori]['products'])->firstWhere('idProduct', $product['idProduct'])
                    : null;

                $totalQtyProductPenjualan = $productPenjualan['totalQty'] ?? 0;

                // Map the targets and add percentage to each target
                $product['target'] = $product['target']->map(function ($target) use ($totalQtyProductPenjualan) {
                    $percentageQtyToTarget = $totalQtyProductPenjualan === 0 ? 0 : ($totalQtyProductPenjualan / $target['totalQtyTarget']) * 100;
                    $target['percentageQtyToTarget'] = $percentageQtyToTarget;
                    return $target;
                });

                // Add totalQty for the product
                $product['totalQty'] = $totalQtyProductPenjualan;

                return $product;
            });
        };

        return [
            'bulk' => [
                'totalQtyTargetKategori' => $totalQtyBulk,
                'totalQtyKategori' => $totalQtyBulkPenjualan,
                'percentageQtyToTargetKategori' => $percentageQtyToTargetBulk,
                'products' => $mapProducts($groupedDataBulk, 'bulk'),
            ],
            'ritel' => [
                'totalQtyTargetKategori' => $totalQtyRitel,
                'totalQtyKategori' => $totalQtyRitelPenjualan,
                'percentageQtyToTargetKategori' => $percentageQtyToTargetRitel,
                'products' => $mapProducts($groupedDataRitel, 'ritel'),
            ],
        ];
    }




    public function indexPeriodPenjualan($tanggalAwal, $tanggalAkhir)
    {
        $data = LaporanPenjualan::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
            ->with('product', 'customer')
            ->get();

        $data = $data->map(function ($item) {
            $item->value = $item->qty * $item->harga_satuan;
            return $item;
        });

        if ($data->isEmpty()) {
            return null;
        }

        // Separate bulk and ritel products
        $bulkData = $data->filter(function ($item) {
            return $item->product->jenis == 'bulk';
        });

        $ritelData = $data->filter(function ($item) {
            return $item->product->jenis == 'ritel';
        });

        // Group and calculate totals for each category (bulk & ritel)
        $bulkCategory = $this->groupAndCalculateTotals($bulkData);
        $ritelCategory = $this->groupAndCalculateTotals($ritelData);

        // Calculate totalQty and totalValue for bulk and ritel
        $totalQtyBulk = $bulkCategory->sum(function ($product) {
            return $product['totalQty'];
        });

        $totalValueBulk = $bulkCategory->sum(function ($product) {
            return $product['totalValue'];
        });

        $totalQtyRetail = $ritelCategory->sum(function ($product) {
            return $product['totalQty'];
        });

        $totalValueRetail = $ritelCategory->sum(function ($product) {
            return $product['totalValue'];
        });

        // Prepare the final response
        return [
            'bulk' => [
                'totalQtyKategori' => $totalQtyBulk,
                'totalValueKategori' => $totalValueBulk,
                'products' => $bulkCategory,
            ],
            'ritel' => [
                'totalQtyKategori' => $totalQtyRetail,
                'totalValueKategori' => $totalValueRetail,
                'products' => $ritelCategory,
            ],
        ];
    }

    private function groupAndCalculateTotals($data)
    {
        // Group by product and calculate totals
        $groupedData = $data->groupBy('product_id');

        return $groupedData->map(function ($items, $productId) {
            $product = $items->first()->product; // Get product details from the first item
            $totalQty = $items->sum('qty');
            $totalValue = $items->sum('value');
            $totalHargaSatuan = $totalValue / $totalQty;

            return [
                'idProduct' => $productId,
                'name' => $product->name,
                'totalQty' => $totalQty,
                'totalValue' => $totalValue,
                'totalHargaSatuan' => $totalHargaSatuan,
                'detail' => $items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'kontrak' => $item->kontrak,
                        'qty' => $item->qty,
                        'harga_satuan' => $item->harga_satuan,
                        'tanggal' => $item->tanggal,
                        'customer_id' => $item->customer_id,
                        'margin_percent' => $item->margin_percent,
                        'created_at' => $item->created_at,
                        'updated_at' => $item->updated_at,
                        'value' => $item->value,
                        'product' => $item->product,
                        'customer' => $item->customer,
                    ];
                }),
            ];
        })->values();
    }

}
