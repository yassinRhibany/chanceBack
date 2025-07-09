<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\investment_offers;
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

private function getFinalOwnership($opprtunty_id)
{
    // جلب الاستثمارات الحالية المرتبطة بالفرصة
    $investments = investments::where('opprtunty_id', $opprtunty_id)->get();

    $ownership = [];

    foreach ($investments as $investment) {
        $userId = $investment->user_id;
        $ownership[$userId] = ($ownership[$userId] ?? 0) + $investment->amount;
    }

    // إزالة من يملك 0 أو أقل فقط كحماية إضافية
    return array_filter($ownership, fn($amount) => $amount > 0);
}
public function distributeReturn(Request $request, $opprtunty_id)
{
    $request->validate([
        'amount' => 'required|numeric|min:1',
    ]);

    $opportunity = investment_opprtunities::with('factory')->findOrFail($opprtunty_id);
    $owner = Auth::user();

    // التأكد أن المستخدم هو مالك المصنع
    if ($opportunity->factory->user_id !== $owner->id) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $returnAmount = $request->amount;

    if ($owner->wallet < $returnAmount) {
        return response()->json(['message' => 'Insufficient balance in owner wallet'], 400);
    }

    $ownership = $this->getFinalOwnership($opprtunty_id);
    $totalOwned = array_sum($ownership);

    if ($totalOwned <= 0) {
        return response()->json(['message' => 'No valid investors found'], 400);
    }

    DB::beginTransaction();
    try {
        foreach ($ownership as $userId => $amountOwned) {
            $investor = User::find($userId);
            $percentage = $amountOwned / $totalOwned;
            $investorReturn = round($percentage * $returnAmount, 2);

            // تحديث محفظة المستثمر
            $investor->wallet += $investorReturn;
            $investor->save();

            // تسجيل حركة التحويل
            transaction::create([
                'user_id' => $investor->id,
                'type' => TrancationType::Return,
                'opportunity_id' => $opprtunty_id,
                'amount' => $investorReturn,
                'time_operation' => now(),
            ]);

            // حفظ سجل العائد
            returns::create([
                'user_id' => $investor->id,
                'opprtunty_id' => $opprtunty_id,
                'amount' => $investorReturn,
                'return_date' => now(),
            ]);
        }

        // خصم من محفظة المالك
        $owner->wallet -= $returnAmount;
        $owner->save();

        // تسجيل حركة السحب من المالك
        transaction::create([
            'user_id' => $owner->id,
            'type' => TrancationType::Return,
            'opportunity_id' => $opprtunty_id,
            'amount' => -$returnAmount,
            'time_operation' => now(),
        ]);

        DB::commit();
        return response()->json(['message' => 'Return distributed successfully']);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
    }
}
 
}
