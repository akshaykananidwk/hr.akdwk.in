<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isManager = $user->hasAnyRole(['Super Admin', 'Sales Manager', 'Accountant']);
        $query = Sale::with(['product', 'user'])->latest();
        if (! $isManager) {
            $query->where('user_id', $user->id);
        }

        return view('sales.index', [
            'sales' => $query->paginate(15),
            'totalRevenue' => (float) (clone $query)->sum('amount'),
        ]);
    }
}
