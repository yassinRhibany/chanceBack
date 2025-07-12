<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\factories;
use App\Models\investment_offers;
use App\Models\investment_opprtunities;
use App\Models\investments;
use App\Models\transaction;
use App\TrancationType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\Offer;
use App\Models\User;

use function Symfony\Component\Clock\now;

class InvestmentOffersController extends Controller
{


    public function index()
{
    $offers = investment_offers::where('status', 0)
        ->with([
            'investment.opprtunty' => function ($query) {
                $query->select('id', 'target_amount', 'collected_amount', 'minimum_target', 'strtup', 'payout_frequency','profit_percentage', 'descrption'); // اختر الأعمدة التي تحتاجها
            }
        ])
        ->select('investment_id','offred_amount', 'price') 
        ->get();

    return response()->json($offers);
}

public function filterByCategory(Request $request)
{
    $query = investment_offers::query();

    if ($request->has('category_id')) {
        $query->whereHas('investment.opprtunty.factory.category', function ($q) use ($request) 
        {
            $q->where('id', $request->category_id);
        });
    }

    $offers = $query->get();

    return response()->json($offers);
}


public function storeOffer(Request $request)
{
    $request->validate([
        'investment_id' => 'required|exists:investments,id',
        'offred_amount' => 'required|numeric|min:0',
        'price' => 'required|numeric|min:0',
    ]);

    $user = Auth::user(); // نحصل على المستخدم من الـ token

    $offer = investment_offers::create([
        'seller_id' => $user->id,
        'investment_id' => $request->investment_id,
        'offred_amount' => $request->offred_amount,
        'price' => $request->price,
        'status' => 0,       // 0= unsell
    ]);

    return response()->json([
        'message' => 'Offer stored successfully',
        'data' => $offer,
    ], 201);
}

public function buyOffer(Request $request)
{
    $request->validate([
        'offer_id' => 'required|integer',
    ]);

    try {
        DB::beginTransaction();

        // قفل العرض لتفادي الشراء المتزامن
        $offer = investment_offers::where('id', $request->offer_id)
            ->where('status', 0)
            ->lockForUpdate()
            ->firstOrFail();

        $seller = User::findOrFail($offer->seller_id);
        $buyer = Auth::user();

        
        // نقل الرصيد
        $buyer->wallet -= $offer->price;
        $seller->wallet += $offer->price;
        $buyer->save();
        $seller->save();

        // تحديث العرض
        $offer->status = 1;
        $offer->sold_at = now();
        $offer->buyer_id = $buyer->id;
        $offer->seller_id = $seller->id;
        $offer->save();

        // استخراج الاستثمار المرتبط بالعرض
        $oldInvestment = investments::findOrFail($offer->investment_id);

        // إنقاص المبلغ من استثمار البائع
        if ($oldInvestment->amount < $offer->offred_amount) {
            return response()->json(['message' => 'قيمة العرض أكبر من الاستثمار المتاح للبائع'], 400);
        }

        $oldInvestment->amount -= $offer->offred_amount;
        $oldInvestment->save();

        // إضافة استثمار جديد للمشتري
        $newInvestment = investments::create([
            'user_id' => $buyer->id,
            'opprtunty_id' => $oldInvestment->opprtunty_id,
            'amount' => $offer->offred_amount,
        ]);

        // تسجيل العمليات المالية مع ربط الفرصة
        transaction::create([
            'user_id' => $buyer->id,
            'amount' => $offer->price,
            'type' => TrancationType::Buy,
            'opprtunty_id' => $oldInvestment->opprtunty_id,
            'time_operation' => now(),
        ]);

        transaction::create([
            'user_id' => $seller->id,
            'amount' => $offer->price,
            'type' => TrancationType::Sell,
            'opprtunty_id' => $oldInvestment->opprtunty_id,
            'time_operation' => now(),
        ]);

        DB::commit();

        return response()->json([
            'message' => 'تمت عملية الشراء بنجاح',
            'offer' => $offer,
            'new_investment' => $newInvestment,
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'حدث خطأ: ' . $e->getMessage()], 500);
    }
}
}
