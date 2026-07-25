<?php

namespace App\Http\Controllers;

use App\Models\Commission;

class CommissionController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $isManager = $user->hasAnyRole(['Super Admin', 'Sales Manager', 'Accountant']);
        $query = Commission::with(['user', 'sale'])->latest();
        if (! $isManager) {
            $query->where('user_id', $user->id);
        }

        return view('commissions.index', [
            'commissions' => $query->paginate(15),
            'wallet' => $user->commissionWallet(),
            'isManager' => $isManager,
        ]);
    }
}
