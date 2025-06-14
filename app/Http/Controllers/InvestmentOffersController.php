<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\factories;
use App\Models\investment_offers;
use App\Models\investment_opprtunities;
use App\Models\investments;
use App\Models\transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\Offer;
use App\Models\User;

class InvestmentOffersController extends Controller
{


    public function index()
{
    $offers = investment_offers::where('status', 0)
        ->with([
            'investment.opprtunty' => function ($query) {
                $query->select( 'target_amount', 'collected_amount', 'minimum_target', 'startup', 'payout_frequency','profit_percentage', 'descrption'); // اختر الأعمدة التي تحتاجها
            }
        ])
        ->select('offred_amount', 'price') 
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
        'investment_id' => 'required|integer',
        'token_seller' => 'required|string',
        'token_buyer' => 'required|string',
    ]);

    try {
        DB::beginTransaction();

        // 1. إيجاد العرض المتاح
        $offer = investment_offers::where('investment_id', $request->investment_id)
            ->where('status', 0)
            ->firstOrFail();

        // 2. إيجاد المستخدمين عن طريق token
        $seller = User::where('api_token', $request->token_seller)->firstOrFail();
        $buyer = User::where('api_token', $request->token_buyer)->firstOrFail();

        // 3. التأكد من أن المشتري يملك رصيد كافٍ
        if ($buyer->wallet < $offer->price) {
            return response()->json(['message' => 'رصيد المشتري غير كافٍ'], 400);
        }

        // 4. نقل الرصيد
        $buyer->wallet -= $offer->price;
        $seller->wallet += $offer->price;

        $buyer->save();
        $seller->save();

        // 5. تحديث العرض
        $offer->status = 1;
        $offer->sold_at = Carbon::now();
        $offer->buyer_id = $buyer->id;
        $offer->seller_id = $seller->id;
        $offer->save();

        
        // تسجيل عمليات المستخدمين
        transaction::create([
            'user_id' => $buyer->id,
            'amount' => $offer->price,
            'type' => 'buy',
        ]);

        transaction::create([
            'user_id' => $seller->id,
            'amount' => $offer->price,
            'type' => 'sell',
        ]);

        DB::commit();

        return response()->json(['message' => 'تمت عملية الشراء بنجاح', 'offer' => $offer]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'حدث خطأ: ' . $e->getMessage()], 500);
    }
}


public function getUserInvestments(Request $request)
{
    $request->validate([
        'token' => 'required|string',
    ]);

    // الحصول على المستخدم بناءً على التوكن
    $user = User::where('api_token', $request->token)->first();


    // الحصول على الاستثمارات مع معلومات الفرص المرتبطة
    $investments = investments::with(['opportunity:id,target_amount,collected_amount,minimum_target,startup,payout_frequency,profit_percentage,descrption'])
        ->where('user_id', $user->id)
        ->get(['id', 'amount', 'opportunity_id']);

    // إعادة تنسيق النتيجة
    $result = $investments->map(function ($investment) {
        return [
            'amount' => $investment->amount,
            'opportunity' => [
                'target' => $investment->opportunity->target,
                'collected' => $investment->opportunity->collected,
                'minimum' => $investment->opportunity->minimum,
                'start' => $investment->opportunity->start,
                'payout_frequency' => $investment->opportunity->payout_frequency,
                'profit_percentage' => $investment->opportunity->profit_percentage,
            ]
        ];
    });

    return response()->json($result);
}



}
