<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
            'role' => $user->role ,
        ]);
    }


    public function getUsers()
{
    $users = User::select('name', 'role','email', 'wallet')
        ->get()
        ->map(function ($user) {
            return [
                'name' => $user->name,
                'role' => $user->role ,
                'email'=> $user->email,
                'wallet'=> $user->wallet,

            ];
        });

    return response()->json($users);
}

public function updateProfile(Request $request)
{
    $user = Auth::user(); // الحصول على المستخدم الحالي

    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email,',
        'password' => 'nullable|string|min:8|confirmed',
    ]);

    $user->name = $request->name;
    $user->email = $request->email;

    if ($request->filled('password')) {
        $user->password = Hash::make($request->password);
    }

    $user->save();

    return response()->json([
        'message' => 'Profile updated successfully.',
        'user' => $user
    ]);
}

    
}
