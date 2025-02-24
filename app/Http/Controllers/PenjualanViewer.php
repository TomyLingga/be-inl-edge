<?php

namespace App\Http\Controllers;

use App\Models\Master\Product;
use App\Models\Penjualan\LaporanPenjualan;
use App\Models\Penjualan\TargetPenjualan;
use Illuminate\Http\Request;
use League\ISO3166\ISO3166;

class PenjualanViewer extends Controller
{

    public function indexPeriodTargetPenjualan($tanggalAwal, $tanggalAkhir)
    {
        // Get all products with target data (left join)
        $products = Product::with(['targetPenjualan' => function ($query) use ($tanggalAwal, $tanggalAkhir) {
            $query->whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])->with('uraian');
        }])->get();

        // Get sales data (penjualan)
        $dataPenjualan = $this->indexPeriodPenjualan($tanggalAwal, $tanggalAkhir);
        $dataPenjualan = $dataPenjualan ?? ['bulk' => ['products' => [], 'totalQtyKategori' => 0], 'ritel' => ['products' => [], 'totalQtyKategori' => 0]];

        // Map products and merge target data
        $groupedData = $products->map(function ($product) {
            $targets = $product->targetPenjualan;

            $targetData = $targets->isNotEmpty()
                ? $targets->groupBy('uraian_id')->map(function ($uraianItems) {
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
                })->values()
                : collect([[ // Ensure empty targets default to 0
                    'nama' => 'Default',
                    'totalQtyTarget' => 0,
                    'detail' => [],
                ]]);

            return [
                'idProduct' => $product->id,
                'name' => $product->name,
                'jenis' => $product->jenis,
                'target' => $targetData,
            ];
        });

        // Filter into bulk and ritel categories
        $groupedDataBulk = $groupedData->filter(fn($item) => $item['jenis'] === 'bulk')->values();
        $groupedDataRitel = $groupedData->filter(fn($item) => $item['jenis'] === 'ritel')->values();

        // Calculate total target quantities for bulk and ritel
        $totalQtyBulk = $groupedDataBulk->sum(fn($item) => $item['target']->sum('totalQtyTarget'));
        $totalQtyRitel = $groupedDataRitel->sum(fn($item) => $item['target']->sum('totalQtyTarget'));

        $totalQtyBulkPenjualan = $dataPenjualan['bulk']['totalQtyKategori'] ?? 0;
        $totalQtyRitelPenjualan = $dataPenjualan['ritel']['totalQtyKategori'] ?? 0;

        $percentageQtyToTargetBulk = $totalQtyBulk === 0 ? 100 : ($totalQtyBulkPenjualan / $totalQtyBulk) * 100;
        $percentageQtyToTargetRitel = $totalQtyRitel === 0 ? 100 : ($totalQtyRitelPenjualan / $totalQtyRitel) * 100;

        // Merge sales data into product targets
        $mapProducts = function ($groupedData, $kategori) use ($dataPenjualan) {
            return $groupedData->map(function ($product) use ($dataPenjualan, $kategori) {
                $productPenjualan = collect($dataPenjualan[$kategori]['products'])->firstWhere('idProduct', $product['idProduct']);
                $totalQtyProductPenjualan = $productPenjualan['totalQty'] ?? 0;

                $product['target'] = $product['target']->map(function ($target) use ($totalQtyProductPenjualan) {
                    $percentageQtyToTarget = $target['totalQtyTarget'] == 0
                        ? 100
                        : ($totalQtyProductPenjualan / $target['totalQtyTarget']) * 100;
                    $target['percentageQtyToTarget'] = $percentageQtyToTarget;
                    return $target;
                });

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

    public function indexLocationPenjualan($tanggalAwal, $tanggalAkhir)
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

        $bulkData = $data->filter(function ($item) {
            return $item->product->jenis == 'bulk';
        })->groupBy('customer.negara')->map(function ($items, $country) {
            return [
                'negara' => $country,
                'code' => $this->getCountryCode($country),
                'qty' => $items->sum('qty'),
                'value' => $items->sum('value'),
            ];
        })->values();

        $ritelData = $data->filter(function ($item) {
            return $item->product->jenis == 'ritel';
        })->groupBy(['customer.negara', 'customer.provinsi'])->map(function ($items, $country) {
            return [
                'negara' => $country,
                'code' => $this->getCountryCode($country),
                'provinsi' => $items->map(function ($provinsiItems, $provinsi) {
                    return [
                        'provinsi' => $provinsi,
                        'code' => $this->getRegionCode($provinsi),
                        'qty' => $provinsiItems->sum('qty'),
                        'value' => $provinsiItems->sum('value'),
                    ];
                })->values(),
            ];
        })->values();

        return [
            'bulk' => $bulkData,
            'ritel' => $ritelData,
        ];
    }

    private function getCountryCode($country)
    {
        $iso3166 = new ISO3166();
        try {
            $countryData = $iso3166->name($country);
            return $countryData['alpha2'];
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getRegionCode($province)
    {
        $regionCodes = [
            'Indonesia' => [
                'Aceh' => 'ID-AC',
                'Bali' => 'ID-BA',
                'Banten' => 'ID-BT',
                'Bengkulu' => 'ID-BE',
                'Gorontalo' => 'ID-GO',
                'DKI Jakarta' => 'ID-JK',
                // 'Jakarta' => 'ID-JK',
                'Jambi' => 'ID-JA',
                'Jawa Barat' => 'ID-JB',
                'Jawa Tengah' => 'ID-JT',
                'Jawa Timur' => 'ID-JI',
                'Kalimantan Barat' => 'ID-KB',
                'Kalimantan Selatan' => 'ID-KS',
                'Kalimantan Tengah' => 'ID-KT',
                'Kalimantan Timur' => 'ID-KI',
                'Kalimantan Utara' => 'ID-KU',
                'Kepulauan Bangka Belitung' => 'ID-BB',
                'Kepulauan Riau' => 'ID-KR',
                'Lampung' => 'ID-LA',
                'Maluku' => 'ID-MA',
                'Maluku Utara' => 'ID-MU',
                'Nusa Tenggara Barat' => 'ID-NB',
                'Nusa Tenggara Timur' => 'ID-NT',
                'Papua' => 'ID-PA',
                'Papua Barat' => 'ID-PB',
                'Riau' => 'ID-RI',
                'Sulawesi Barat' => 'ID-SR',
                'Sulawesi Selatan' => 'ID-SN',
                'Sulawesi Tengah' => 'ID-ST',
                'Sulawesi Tenggara' => 'ID-SG',
                'Sulawesi Utara' => 'ID-SA',
                'Sumatera Barat' => 'ID-SB',
                'Sumatera Selatan' => 'ID-SS',
                'Sumatera Utara' => 'ID-SU',
                // 'DI Yogyakarta' => 'ID-YO',
                'Yogyakarta' => 'ID-YO',
            ]
        ];

        // Ensure 'Indonesia' key exists before accessing
        if (isset($regionCodes['Indonesia'][$province])) {
            return $regionCodes['Indonesia'][$province];
        }

        foreach ($regionCodes['Indonesia'] as $key => $code) {
            if (str_contains(strtolower($province), strtolower($key))) {
                return $code;
            }
        }

        return null;
    }

}
