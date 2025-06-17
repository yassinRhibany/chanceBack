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

        // 1. إيجاد العرض المتاح عن طريق offer_id
        $offer = investment_offers::where('id', $request->offer_id)
            ->where('status', 0)
            ->firstOrFail();

        // 2. إيجاد البائع عن طريق token
        $seller = User::where('id', $offer->seller_id)->firstOrFail();

        // 3. المستخدم المصادق عليه هو المشتري
        $buyer = Auth::user();

        // التأكد من أن المشتري لا يشتري من نفسه
        if ($buyer->id === $seller->id) {
            return response()->json(['message' => 'لا يمكنك شراء العرض الخاص بك'], 400);
        }

        // 4. التأكد من أن المشتري يملك رصيد كافٍ
        if ($buyer->wallet < $offer->price) {
            return response()->json(['message' => 'رصيد المشتري غير كافٍ'], 400);
        }

        // 5. نقل الرصيد
        $buyer->wallet -= $offer->price;
        $seller->wallet += $offer->price;

        $buyer->save();
        $seller->save();

        // 6. تحديث العرض
        $offer->status = 1;
        $offer->sold_at = Carbon::now();
        $offer->buyer_id = $buyer->id;
        $offer->seller_id = $seller->id;
        $offer->save();

        // 7. تسجيل العمليات
        transaction::create([
            'user_id' => $buyer->id,
            'amount' => $offer->price,
            'type' => TrancationType::Buy,
            'time_operation' => now(),
        ]);

        transaction::create([
            'user_id' => $seller->id,
            'amount' => $offer->price,
            'type' => TrancationType::Sell,
            'time_operation' => now(),

        ]);

        DB::commit();

        return response()->json(['message' => 'تمت عملية الشراء بنجاح', 'offer' => $offer]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'حدث خطأ: ' . $e->getMessage()], 500);
    }
}









// public function buyOffer(Request $request)
// {
//     $request->validate([
//         'investment_id' => 'required|integer',
//         'token_seller' => 'required|string',
//         'token_buyer' => 'required|string',
//     ]);

//     try {
//         DB::beginTransaction();

//         // 1. إيجاد العرض المتاح
//         $offer = investment_offers::where('investment_id', $request->investment_id)
//             ->where('status', 0)
//             ->firstOrFail();

//         // 2. إيجاد المستخدمين عن طريق token
//         $seller = User::where('api_token', $request->token_seller)->firstOrFail();
//         $buyer = User::where('api_token', $request->token_buyer)->firstOrFail();

//         // 3. التأكد من أن المشتري يملك رصيد كافٍ
//         if ($buyer->wallet < $offer->price) {
//             return response()->json(['message' => 'رصيد المشتري غير كافٍ'], 400);
//         }

//         // 4. نقل الرصيد
//         $buyer->wallet -= $offer->price;
//         $seller->wallet += $offer->price;

//         $buyer->save();
//         $seller->save();

//         // 5. تحديث العرض
//         $offer->status = 1;
//         $offer->sold_at = Carbon::now();
//         $offer->buyer_id = $buyer->id;
//         $offer->seller_id = $seller->id;
//         $offer->save();

        
//         // تسجيل عمليات المستخدمين
//         transaction::create([
//             'user_id' => $buyer->id,
//             'amount' => $offer->price,
//             'type' => TrancationType::Buy,
//         ]);

//         transaction::create([
//             'user_id' => $seller->id,
//             'amount' => $offer->price,
//             'type' => TrancationType::Sell,
//         ]);

//         DB::commit();

//         return response()->json(['message' => 'تمت عملية الشراء بنجاح', 'offer' => $offer]);

//     } catch (\Exception $e) {
//         DB::rollBack();
//         return response()->json(['message' => 'حدث خطأ: ' . $e->getMessage()], 500);
//     }
// }

}
