<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    
    
    public function index()
    {
        $transaction = transaction::all();
        return response()->json($transaction);
    }



    public function getUserTransactions(Request $request)
{
    $user = Auth::user();

    if (!$user) {
        return response()->json(['message' => 'المستخدم غير موجود'], 404);
    }

    $transactions = Transaction::where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json([
        'user' => $user->name,
        'transactions' => $transactions
    ]);
}



}
