<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\transaction;
use App\Models\User;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    
    
    public function index()
    {
        $transaction = transaction::all();
        return response()->json($transaction);
    }



    public function getUserTransactions(Request $request)
{
    $request->validate([
        'token' => 'required|string',
    ]);

    $user = User::where('api_token', $request->token)->first();

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
