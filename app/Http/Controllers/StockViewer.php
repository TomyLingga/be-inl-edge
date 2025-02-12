<?php

namespace App\Http\Controllers;

use App\Models\Stock\StockBulk;
use App\Models\Stock\StockCpo;
use App\Models\Stock\StockRetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockViewer extends Controller
{
    public function indexPeriodStockCpo($tanggalAwal, $tanggalAkhir)
    {
        $data = StockCpo::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
            ->with('tanki.lokasi')
            ->get();

        if ($data->isEmpty()) {
            return null;
        }

        $latestStockCpo = $data->groupBy('tanki_id')->map(function ($group) {
            return $group->sortByDesc('tanggal')->first();
        })->values();

        $totalStock = 0;
        foreach ($latestStockCpo as $stock) {
            $stock->space = $stock->tanki->kapasitas - $stock->qty;

            $totalStock += $stock->qty;
        }

        // Return latest stock and total stock
        return [
            'totalStock' => $totalStock,
            'data' => $latestStockCpo,
        ];
    }

    public function indexPeriodStockBulk($tanggalAwal, $tanggalAkhir)
    {
        $data = StockBulk::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
            ->with(['tanki.lokasi', 'product'])
            ->get();

        if ($data->isEmpty()) {
            return null;
        }

        $chartData = $data->groupBy('id_bulky')->map(function ($group) {
            return $group;
        })->values();

        $latestStockBulk = $data->groupBy('tanki_id')->map(function ($group) {
            return $group->sortByDesc('tanggal')->first();
        })->values();

        foreach ($latestStockBulk as $stock) {
            $stock->space = $stock->tanki->kapasitas - $stock->qty;
        }

        $totals = $latestStockBulk->groupBy('product.name')->map(function ($group) {
            return [
                'product_name' => $group->first()->product->name,
                'total' => $group->sum('qty'),
            ];
        })->values();

        // Return both total quantities and the latest stock data
        return [
            'total' => $totals,
            'details' => $latestStockBulk,
            'chart' => $chartData,
        ];
    }

    public function indexPeriodStockRetail($tanggalAwal, $tanggalAkhir)
    {
        // Fetch data based on the given date range and relations
        $dataPeriod = StockRetail::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
            ->with(['warehouse.lokasi', 'product'])
            ->orderBy('tanggal', 'desc') // Ensure records are ordered by date in descending order
            ->get();

        $chartData = $dataPeriod->groupBy('id_ritel')->map(function ($group) {
            return $group;
        })->values();

        $data = StockRetail::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
            ->with(['warehouse.lokasi', 'product'])
            ->select('id', 'id_ritel', 'warehouse_id', 'tanggal', 'qty', 'umur', 'remarks', 'created_at', 'updated_at')
            ->orderBy('tanggal', 'desc') // Ensure records are ordered by date in descending order
            ->get()
            ->groupBy('warehouse_id'); // Group by warehouse_id

        // Initialize variables to accumulate total quantities
        $totalQty = 0;
        $totalTon = 0;
        $productsTotal = [];

        // Map through grouped data and calculate totals
        $data = $data->map(function ($group) use (&$totalQty, &$totalTon, &$productsTotal) {
            $groupedByProduct = $group->groupBy('id_ritel'); // Group by product (id_ritel) within each warehouse

            $warehouseQty = 0;
            $warehouseTon = 0;
            $warehousePallet = 0;

            // Calculate totals per warehouse and keep track of product totals
            $details = $groupedByProduct->map(function ($productGroup) use (&$warehouseQty, &$warehouseTon, &$warehousePallet, &$productsTotal) {
                $latestItem = $productGroup->first(); // Get the most recent entry per product

                // Calculate qtyTon and qtyPallet for the product
                $latestItem->qtyTon = number_format((float) $latestItem->qty * (float) $latestItem->product->konversi_ton, 2, '.', '');
                $latestItem->qtyPallet = round((float) $latestItem->qty / (float) $latestItem->product->konversi_pallet);

                // Update warehouse totals
                $warehouseQty += (float) $latestItem->qty;
                $warehouseTon += (float) $latestItem->qtyTon;
                $warehousePallet += (float) $latestItem->qtyPallet;

                // Update product totals (across all warehouses)
                if (!isset($productsTotal[$latestItem->product->name])) {
                    $productsTotal[$latestItem->product->name] = [
                        'totalQty' => 0,
                        'totalTon' => 0
                    ];
                }

                // Aggregate product total
                $productsTotal[$latestItem->product->name]['totalQty'] += (float) $latestItem->qty;
                $productsTotal[$latestItem->product->name]['totalTon'] += (float) $latestItem->qtyTon;

                return $latestItem;
            })->values();

            // Add to global totals
            $totalQty += $warehouseQty;
            $totalTon += $warehouseTon;

            return [
                'warehouseName' => $group->first()->warehouse->name,
                'warehouseCapacity' => $group->first()->warehouse->kapasitas,
                'warehouseQty' => number_format($warehouseQty, 2, '.', ''),
                'warehouseTon' => number_format($warehouseTon, 2, '.', ''),
                'warehousePallet' => round($warehousePallet),
                'warehouseSpace' => $group->first()->warehouse->kapasitas - round($warehousePallet),
                'warehouseUtilisasiPercent' => round($warehousePallet) / $group->first()->warehouse->kapasitas * 100,
                'detail' => $details
            ];
        })->values();

        // Prepare the 'total' item for the response
        $total = [
            'totalQty' => number_format($totalQty, 2, '.', ''),
            'totalTon' => number_format($totalTon, 2, '.', ''),
            'products' => array_map(function ($productName, $totals) {
                return [
                    'product_name' => $productName,
                    'totalQty' => number_format($totals['totalQty'], 2, '.', ''),
                    'totalTon' => number_format($totals['totalTon'], 2, '.', '')
                ];
            }, array_keys($productsTotal), $productsTotal)
        ];

        // Return the data with the total information
        return [
            'total' => $total,
            'warehouse' => $data,
            'chart' => $chartData,
        ];
    }

}
