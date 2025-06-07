<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\investment_opprtunities;
use App\Models\investments;
use App\Models\User;
use Illuminate\Http\Request;

class InvestmentsController extends Controller
{
    
    public function index()
    {
        $investments = investments::all();
        return response()->json($investments);
    }

    public function show($id)
    {
        $investments = investments::findOrFail($id);
        return response()->json($investments);
    }

    public function store(Request $request)
    {
        
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'opprtunty_id' => 'required|exists:investment_opprtunities,id',
            'amount' => 'required|numeric',
        ]);
        $user = User::where('id',$request->user_id)->get();
        $investmantopprtunity = investment_opprtunities::where('investment_opprtunities_id',$request->investment_opprtunities_id)->get();
       
        if ($request->amount >= $investmantopprtunity->minimum_target) {
            $investments = investments::create($request->only([
                'user_id', 'opprtunty_id', 'amount'
            ]));
            
            return response()->json($investments, 201);
            
        }

       


       
    }

    public function update(Request $request, $id)
    {
        $investments = investments::findOrFail($id);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'opprtunty_id' => 'required|exists:investment_opprtunities,id',
            'amount' => 'required|numeric',
        ]);

        $investments = investments::update($request->only([
            'user_id', 'opprtunty_id', 'amount'
        ]));

        return response()->json($investments);
    }

    public function destroy($id)
    {
        $investments = investments::findOrFail($id);
        $investments->delete();

        return response()->json(['message' => 'investments deleted successfully']);
    }

    public function filterByUser($user_id)
{
    $investments = investments::where('user_id', $user_id)->get();
    return response()->json($investments);
}

public function purchase(Request $request){
    $user = User::where('id',$request->user_id)->get();
    $investmantopprtunity = investment_opprtunities::where('investment_opprtunities_id',$request->investment_opprtunities_id)->get();
   
    
}

}
