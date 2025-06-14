<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class UserController extends Controller
{
    
    // تابع عرض معلومات المستخدم
    public function showProfile()
    {
        $user=Auth::user();
        if (!$user) {
            return response()->json(['message' => 'المستخدم غير موجود'], 404);
        }

        return response()->json([
            'name'    => $user->name,
            'email'   => $user->email,
            'wallet'  => $user->wallet,
        ]);
    }


    public function getUsersWithRoles()
{
    $users = User::select('name', 'role', 'address')
        ->get()
        ->map(function ($user) {
            return [
                'name' => $user->name,
                'role' => $user->role == 1 ? 'Owner' : 'Investor',
                'address' => $user->address,
            ];
        });

    return response()->json($users);
}

    
}
