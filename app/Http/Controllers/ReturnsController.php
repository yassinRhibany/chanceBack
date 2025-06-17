<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\investment_opprtunities;
use App\Models\investments;
use App\Models\returns;
use App\Models\transaction;
use App\Models\User;
use App\TrancationType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReturnsController extends Controller
{
    
public function getUserReturns(Request $request)
{
    $user =Auth::user();
    $returns = returns::with([
        'investment.opprtunty:id,descrption'
    ])
    ->whereHas('investment', function($query) use ($user) {
        $query->where('user_id', $user->id);
    })
    ->orderBy('created_at', 'desc')
    ->get();

    // مجموع العائدات
    $total = $returns->sum('amount');

    // إعادة بناء الاستجابة
    $formatted = $returns->map(function ($return) {
        $investmentAmount = $return->investment->amount ?? 1; // تأمين ضد القسمة على صفر
        $percentage = ($return->amount / $investmentAmount) * 100;

        return [
            'return_value' => $return->amount, // ← قيمة العائد الحقيقية
            'return_share' => round($percentage, 2), // ← النسبة كرقم
            'description' => optional($return->investment->opprtunty)->descrption,
            'created_at' => $return->created_at->toDateTimeString(),
        ];
    });

    return response()->json([
        'total_returns' => $total,
        'details' => $formatted,
    ]);
}


public function distributeReturn(Request $request, $opportunityId)
{
    $request->validate([
        'amount' => 'required|numeric|min:1',
    ]);

    $opportunity = investment_opprtunities::with('factory')->findOrFail($opportunityId);
    $owner = Auth::user();

    // التحقق أن المستخدم هو مالك المصنع
    if ($opportunity->factory->user_id !== $owner->id) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $investments = investments::where('opportunities_id', $opportunityId)->get();
    $target = $opportunity->target_amount;
    $returnAmount = $request->amount;

    if ($owner->wallet < $returnAmount) {
        return response()->json(['message' => 'Insufficient balance in owner wallet'], 400);
    }

    DB::beginTransaction();
    try {
        foreach ($investments as $investment) {
            $investor = User::find($investment->user_id);
            $percentage = $investment->amount / $target;
            $investorReturn = round($percentage * $returnAmount, 2);

            // تحديث محفظة المستثمر
            $investor->wallet += $investorReturn;
            $investor->save();

            // تسجيل الحركة
            transaction::create([
                'user_id' => $investor->id,
                'type' => TrancationType::Return,
                'amount' => $investorReturn,
                'time' => now(),
            ]);

            // إضافة السجل إلى جدول returns
            returns::create([
                'investment_id' => $investment->id,
                'amount' => $investorReturn,
                'return_date' => now(),
            ]);
        }

        // خصم المبلغ من محفظة المالك
        $owner->wallet -= $returnAmount;
        // $owner->save();

        // تسجيل حركة السحب للمالك
        transaction::create([
            'user_id' => $owner->id,
            'type' => 'return_payment',
            'amount' => -$returnAmount,
            'time' => now(),
        ]);

        DB::commit();

        return response()->json(['message' => 'Return distributed successfully']);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
    }
}
 
}
