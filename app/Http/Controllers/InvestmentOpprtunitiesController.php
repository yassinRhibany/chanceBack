<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\factories;
use App\Models\investment_opprtunities;
use App\Models\investments;
use App\Models\transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
class InvestmentOpprtunitiesController extends Controller
{
    

    public function storeoppertunitiy(Request $request, $factory_id)
{
    $request->validate([
        'target_amount' => 'required|numeric|min:0',
        'minimum_amount' => 'required|numeric|min:0',
        'startup' => 'required|numeric|min:0',
        'payout_frequency' => 'required|string',
        'profit_percentage' => 'required|numeric|min:0|max:100',
        'description' => 'required|string',
    ]);

    $factory = factories::findOrFail($factory_id);
       if ($factory->status !== 'approved') {
        return response()->json([
            'message' => 'Opportunity cannot be created. Factory is not approved.',
        ], 403);
    }

    $opportunity = investment_opprtunities::create([
        'user_id' => Auth::id(),
        'factory_id' => $factory->id,
        'target_amount' => $request->target_amount,
        'collected_amount' => 0, // تبدأ من صفر
        'minimum_amount' => $request->minimum_amount,
        'startup' => $request->startup,
        'payout_frequency' => $request->payout_frequency,
        'profit_percentage' => $request->profit_percentage,
        'description' => $request->description,
    ]);

    return response()->json([
        'message' => 'Opportunity created successfully.',
        'opportunity' => $opportunity,
    ], 201);
}

public function updateOpportunity(Request $request, $id)
{
    // ✅ تحقق من صحة البيانات
    $request->validate([
        'target_amount' => 'numeric|min:0|nullable',
        'collected_amount' => 'numeric|min:0|nullable',
        'minimum_amount' => 'numeric|min:0|nullable',
        'factory_id' => 'exists:factories,id|nullable',
        'startup' => 'boolean|nullable',
        'payout_frequency' => 'string|nullable',
        'profit_percentage' => 'numeric|min:0|max:100|nullable',
        'description' => 'string|nullable',
    ]);

    // ✅ جلب الـ Opportunity
    $opportunity = investment_opprtunities::findOrFail($id);

    // ✅ التعديل
    $opportunity->update($request->only([
        'target_amount',
        'collected_amount',
        'minimum_amount',
        'factory_id',
        'startup',
        'payout_frequency',
        'profit_percentage',
        'description'
    ]));

    return response()->json([
        'message' => 'Opportunity updated successfully.',
        'opportunity' => $opportunity
    ]);
}
    public function getAcceptedOpportunitiesWithDetails()
{
    $opportunities = investment_opprtunities::whereHas('factory', function ($query) {
        $query->where('status', 'approved');
    })
    ->with([
        'factory' => function ($q) {
            $q->select( 'name', 'feasibility', 'address', 'category_id', 'status')
              ->where('status', 'approved')
              ->with(['category:id,name']);
        }
    ])
    ->get(['id', 'target_amount', 'collected_amount','minimum_target','startup', 'payout_frequency', 'profit_percentage', 'descrption']); 

    // إعادة بناء الاستجابة
    $formatted = $opportunities->map(function ($opportunity) {
        return [
            'opportunity_id' => $opportunity->id,
            'opportunity_target_amount'=> $opportunity->target_amount,
            'opportunity_collected_amount'=> $opportunity->collected_amount,
            'opportunity_minimum_target'=> $opportunity->minimum_target,
            'opportunity_startup'=> $opportunity->startup,
            'opportunity_payout_frequency'=> $opportunity->payout_frequency,
            'opportunity_profit_percentage'=> $opportunity->profit_percentage,
            'opportunity_descrption' => $opportunity->descrption ,
            'factory_name' => optional($opportunity->factory)->name,
            'factory_address' => optional($opportunity->factory)->address,
            'factory_feasibility' => optional($opportunity->factory)->feasibility,
            'category_name' => optional($opportunity->factory->category)->name,
        ];
    });

    return response()->json($formatted);
}
   
public function getFactoryOpportunities($factoryId)
{
    $factory = factories::with('opportunities')->findOrFail($factoryId);

    return response()->json([
        'factory' => $factory->name,
        'opportunities' => $factory->opportunities,
    ]);
}


public function getOpportunitiesByCategory(Request $request)
{
    $categoryId = $request->input('category_id');

    $opportunities = investment_opprtunities::whereHas('factory', function ($query) use ($categoryId) {
        $query->where('status', 'approved')
              ->where('category_id', $categoryId);
    })
    ->with([
        'factory' => function ($q) use ($categoryId) {
            $q->select('id', 'name', 'feasibility', 'address', 'category_id', 'status')
              ->where('status', 'approved')
              ->where('category_id', $categoryId)
              ->with(['category:id,name']);
        }
    ])
    ->get(['id', 'target_amount', 'collected_amount','minimum_target','startup', 'payout_frequency', 'profit_percentage', 'descrption', 'factory_id']);

    // إعادة بناء الاستجابة
    $formatted = $opportunities->map(function ($opportunity) {
        return [
            'opportunity_id' => $opportunity->id,
            'opportunity_target_amount'=> $opportunity->target_amount,
            'opportunity_collected_amount'=> $opportunity->collected_amount,
            'opportunity_minimum_target'=> $opportunity->minimum_target,
            'opportunity_startup'=> $opportunity->startup,
            'opportunity_payout_frequency'=> $opportunity->payout_frequency,
            'opportunity_profit_percentage'=> $opportunity->profit_percentage,
            'opportunity_descrption' => $opportunity->descrption,
            'factory_name' => optional($opportunity->factory)->name,
            'factory_address' => optional($opportunity->factory)->address,
            'factory_feasibility' => optional($opportunity->factory)->feasibility,
            'category_name' => optional($opportunity->factory->category)->name,
        ];
    });

    return response()->json($formatted);
}



public function confirmPurchase(Request $request)
{
    $request->validate([
        'opportunity_id' => 'required|exists:investment_opprtunities,id',
        'token' => 'required|string',
        'amount' => 'required|numeric|min:1',
    ]);

    // جلب المستخدم من التوكن
    $user = User::where('api_token', $request->token)->first();
    if (!$user) {
        return response()->json(['message' => 'Invalid user token'], 401);
    }

    $opportunity = investment_opprtunities::find($request->opportunity_id);

    // التحقق من مبلغ الشراء
    if ($request->amount < $opportunity->minimum_target || $request->amount > $opportunity->target_amount) {
        return response()->json(['message' => 'Amount must be between minimum and target'], 422);
    }

    // التحقق من توفر الرصيد
    if ($user->wallet < $request->amount) {
        return response()->json(['message' => 'Insufficient wallet balance'], 422);
    }

    DB::beginTransaction();
    try {
        // إنقاص المبلغ من المحفظة
        $user->wallet -= $request->amount;
        $user->save();

        $opportunity->collected_amount += $request->amount;

        // تسجيل العملية في جدول transaction
        transaction::create([
            'user_id' => $user->id,
            'amount' => $request->amount,
            'type' => 'buy',
            'created_at' => now(),
        ]);

        // تسجيل الاستثمار (تثبيت العملية)
        investments::create([
            'user_id' => $user->id,
            'opportunity_id' => $opportunity->id,
            'amount' => $request->amount,
        ]);

        DB::commit();

        return response()->json(['message' => 'Purchase confirmed successfully']);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'Failed to confirm purchase', 'error' => $e->getMessage()], 500);
    }
}

  
}
