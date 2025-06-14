<?php

namespace App\Http\Controllers;

use App\factoryStatus;
use App\Http\Controllers\Controller;
use App\Models\factories;
use App\Models\investment_opprtunities;
use App\Models\investments;
use App\Models\transaction;
use App\Models\User;
use App\TrancationType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
class InvestmentOpprtunitiesController extends Controller
{
    

    public function storeoppertunitiy(Request $request, $factory_id)
{
    $request->validate([
        'target_amount' => 'required|numeric|min:0',
        'minimum_target' => 'required|numeric|min:0',
        'strtup' => 'required|date',
        'payout_frequency' => 'required|string',
        'profit_percentage' => 'required|numeric|min:0|max:100',
        'descrption' => 'required|string',
    ]);

    $factory = factories::findOrFail($factory_id);
       if ($factory->status !== factoryStatus::Approved) {
        return response()->json([
            'message' => 'Opportunity cannot be created. Factory is not approved.',
        ], 403);
    }

    $opportunity = investment_opprtunities::create([
        'user_id' => Auth::id(),
        'factory_id' => $factory->id,
        'target_amount' => $request->target_amount,
        'collected_amount' => 0, // تبدأ من صفر
        'minimum_target' => $request->minimum_target,
        'strtup' => $request->strtup,
        'payout_frequency' => $request->payout_frequency,
        'profit_percentage' => $request->profit_percentage,
        'descrption' => $request->descrption,
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
        'strtup' => 'date|nullable',
        'payout_frequency' => 'string|nullable',
        'profit_percentage' => 'numeric|min:0|max:100|nullable',
        'descrption' => 'string|nullable',
    ]);

    // ✅ جلب الـ Opportunity
    $opportunity = investment_opprtunities::findOrFail($id);

    // ✅ التعديل
    $opportunity->update($request->only([
        'target_amount',
        'collected_amount',
        'minimum_amount',
        'strtup',
        'payout_frequency',
        'profit_percentage',
        'descrption'
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
            $q->select('id', 'name', 'feasibility_pdf', 'address', 'category_id', 'status')
              ->where('status', 'approved')
              ->with(['category:id,name']);
        }
    ])
    ->get(['id', 'target_amount', 'collected_amount', 'minimum_target', 'strtup', 'payout_frequency', 'profit_percentage', 'descrption', 'factory_id']); 

    $formatted = $opportunities->map(function ($opportunity) {
        $factory = $opportunity->factory;
        $category = $factory ? $factory->category : null;

        return [
            'opportunity_id' => $opportunity->id,
            'opportunity_target_amount'=> $opportunity->target_amount,
            'opportunity_collected_amount'=> $opportunity->collected_amount,
            'opportunity_minimum_target'=> $opportunity->minimum_target,
            'opportunity_strtup'=> $opportunity->strtup,
            'opportunity_payout_frequency'=> $opportunity->payout_frequency,
            'opportunity_profit_percentage'=> $opportunity->profit_percentage,
            'opportunity_description' => $opportunity->descrption,
            'factory_name' => $factory?->name,
            'factory_address' => $factory?->address,
            'factory_feasibility_pdf' => $factory?->feasibility_pdf,
            'category_name' => $category?->name,
        ];
    });

    return response()->json($formatted);
}
   
public function getFactoryOpportunities($factoryId)
{
    $factory = factories::with(relations: 'investment_opprtunities')->findOrFail($factoryId);

    return response()->json([
        'factory' => $factory->name,
        'opportunities' => $factory->investment_opprtunities,
    ]);
}



public function getOpportunitiesByCategory( $categoryId)
{
    $opportunities = investment_opprtunities::whereHas('factory', function ($query) use ($categoryId) {
        $query->where('status', 'approved')
              ->where('category_id', $categoryId);
    })
    ->with([
        'factory' => function ($q) use ($categoryId) {
            $q->select('id', 'name', 'feasibility_pdf', 'address', 'category_id', 'status')
              ->where('status', 'approved')
              ->where('category_id', $categoryId)
              ->with(['category:id,name']);
        }
    ])
    ->get(['id', 'target_amount', 'collected_amount','minimum_target','strtup', 'payout_frequency', 'profit_percentage', 'descrption', 'factory_id']);

    // إعادة بناء الاستجابة
    $formatted = $opportunities->map(function ($opportunity) {
        return [
            'opportunity_id' => $opportunity->id,
            'opportunity_target_amount'=> $opportunity->target_amount,
            'opportunity_collected_amount'=> $opportunity->collected_amount,
            'opportunity_minimum_target'=> $opportunity->minimum_target,
            'opportunity_strtup'=> $opportunity->strtup,
            'opportunity_payout_frequency'=> $opportunity->payout_frequency,
            'opportunity_profit_percentage'=> $opportunity->profit_percentage,
            'opportunity_descrption' => $opportunity->descrption,
            'factory_name' => optional($opportunity->factory)->name,
            'factory_address' => optional($opportunity->factory)->address,
            'factory_feasibility' => optional($opportunity->factory)->feasibility_pdf,
            'category_name' => optional($opportunity->factory->category)->name,
        ];
    });

    return response()->json($formatted);
}



public function confirmPurchase(Request $request)
{
    $user=Auth::user();
    
    $request->validate([
        'opprtunty_id' => 'required|exists:investment_opprtunities,id',
        'amount' => 'required|numeric|min:1',
    ]);

  
    

    if (!$user) {
        return response()->json(['message' => 'unauthorized  '], 401);
    }

    $opportunity = investment_opprtunities::find($request->opprtunty_id);

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

        $opportunity->collected_amount += $request->amount;

        // تسجيل العملية في جدول transaction
        transaction::create([
            'user_id' => $user->id,
            'amount' => $request->amount,
            'type' => TrancationType::Buy,
            'time_operation' => now(),
        ]);

        // تسجيل الاستثمار (تثبيت العملية)
        investments::create([
            'user_id' => $user->id,
            'opprtunty_id' => $opportunity->id,
            'amount' => $request->amount,
        ]);
        $opportunity->save();
        $user->save();

        
        DB::commit();
        

        return response()->json(['message' => 'Purchase confirmed successfully']);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'Failed to confirm purchase', 'error' => $e->getMessage()], 500);
    }
}

  
}
