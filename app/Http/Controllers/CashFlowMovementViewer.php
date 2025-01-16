<?php

namespace App\Http\Controllers;

use App\Models\CashFlowMovement\CashFlowMovement;
use Illuminate\Http\Request;

class CashFlowMovementViewer extends Controller
{
    public function indexPeriodCashFlowMovement($tanggalAwal, $tanggalAkhir, $idPmg)
    {
        $data = CashFlowMovement::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
            ->where('pmg_id', $idPmg)
            ->with('kategori', 'pmg')
            ->get();

        if ($data->isEmpty()) {
            return null;
        }

        $endingCashBalanced = $data->reduce(function ($carry, $item) {
            return $carry + ($item->kategori->nilai === 'positive' ? $item->value : -$item->value);
        }, 0);

        return [
            'data' => $data,
            'ending_cash_balanced' => $endingCashBalanced,
        ];
    }
}
