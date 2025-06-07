<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    
    // تابع عرض معلومات المستخدم
    public function showProfile(Request $request)
    {
        $user = User::where('api_token', $request->token)->first();

        if (!$user) {
            return response()->json(['message' => 'المستخدم غير موجود'], 404);
        }

        return response()->json([
            'name'    => $user->name,
            'address' => $user->address,
            'email'   => $user->email,
            'wallet'  => $user->wallet,
        ]);
    }

    
    // تابع تعديل الاسم والعنوان
    public function updateProfile(Request $request)
    {
        $request->validate([
            'token'   => 'required|string',
            'name'    => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
        ]);

        $user = User::where('api_token', $request->token)->first();

        if (!$user) {
            return response()->json(['message' => 'المستخدم غير موجود'], 404);
        }

        // تحديث فقط إذا تم إرسال بيانات
        if ($request->filled('name')) {
            $user->name = $request->name;
        }

        if ($request->filled('address')) {
            $user->address = $request->address;
        }

        $user->save();

        return response()->json(['message' => 'تم تحديث الملف الشخصي بنجاح']);
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
