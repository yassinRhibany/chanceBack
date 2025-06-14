<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\investment_opprtunities;
use App\Models\investments;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvestmentsController extends Controller
{
    public function index()
{
    $investments = investments::with([
        'opprtunty:id,factory_id,target_amount,collected_amount,minimum_target,payout_frequency,profit_percentage,descrption',
        'opprtunty.factory:id,name'
    ])
    ->get(['id', 'opprtunty_id', 'amount', 'user_id']);

    $result = $investments->map(function ($investment) {
        $opportunity = $investment->opprtunty;
        $target = $opportunity->target_amount ?? 0;

        $percentage = ($target > 0)
            ? round(($investment->amount / $target) * 100, 2)
            : 0;

        return [
            'user_id' => $investment->user_id,
            'amount' => $investment->amount,
            'percentage' => $percentage, // ✅ تمت إضافتها
            'opportunity' => [
                'target_amount' => $target,
                'collected_amount' => $opportunity->collected_amount ?? null,
                'minimum_target' => $opportunity->minimum_target ?? null,
                'payout_frequency' => $opportunity->payout_frequency ?? null,
                'profit_percentage' => $opportunity->profit_percentage ?? null,
                'descrption' => $opportunity->descrption ?? null,
                'factory_name' => $opportunity->factory->name ?? null,
            ],
        ];
    });

    return response()->json($result);
}
    


public function filterByUser()
{
    $user = Auth::user();

    $investments = investments::with([
        'opprtunty:id,factory_id,target_amount,collected_amount,minimum_target,payout_frequency,profit_percentage,descrption',
        'opprtunty.factory:id,name'
    ])
    ->where('user_id', $user->id)
    ->get(['id', 'opprtunty_id', 'amount']);

    $result = $investments->map(function ($investment) {
        $opportunity = $investment->opprtunty;
        $target = $opportunity->target_amount ?? 0;

        $percentage = ($target > 0)
            ? round(($investment->amount / $target) * 100, 2)
            : 0;

        return [
            'amount' => $investment->amount,
            'percentage' => $percentage, // ✅ تمت إضافتها
            'opportunity' => [
                'target_amount' => $target,
                'minimum_target' => $opportunity->minimum_target ?? null,
                'payout_frequency' => $opportunity->payout_frequency ?? null,
                'profit_percentage' => $opportunity->profit_percentage ?? null,
                'collected_amount' => $opportunity->collected_amount ?? null,
                'descrption' => $opportunity->descrption ?? null,
                'factory_name' => $opportunity->factory->name ?? null,
            ],
        ];
    });

    return response()->json($result);
}

}
