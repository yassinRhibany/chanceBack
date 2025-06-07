<?php

namespace App\Http\Controllers;
use App\factoryStatus;
use App\Models\factories;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class FactoriesController extends Controller
{

    
public function indexForUser()
{
    $user = Auth::user();

    $factories = factories::with('category')  
        ->where('user_id', $user->id)
        ->get();

    return response()->json([
        'factories' => $factories
    ]);
}

public function getAllFactories()
{
    $factories = factories::with('category') // لجلب بيانات التصنيف أيضاً
        ->select('id', 'name', 'address', 'status', 'is_active', 'category_id')
        ->get();

    return response()->json($factories);
}


public function updateFactoryStatus(Request $request, $id)
{
    $request->validate([
    'status' => ['required', Rule::in([
        FactoryStatus::Approved->value,
        FactoryStatus::Rejacter->value,
    ])],
]);

    $factory = factories::findOrFail($id);

    if ($factory->status !== factoryStatus::Pending) {
        return response()->json(['message' => 'Status already set'], 400);
    }

    $factory->status = factoryStatus::from($request->status);
    $factory->save();

    return response()->json(['message' => 'Status updated successfully']);
}
public function updateFactory(Request $request,$id)
{
    $request->validate([
        'name' => 'string|nullable',
        'address' => 'string|nullable',
        'feasibility_pdf' => 'required|file|mimes:pdf',
        'is_active' => 'boolean|nullable',
    ]);

    $factory = factories::findOrFail($id);

    $factory->update($request->only('name', 'address', 'feasibility_pdf', 'is_active'));

    return response()->json(['message' => 'Factory updated successfully', 'factory' => $factory]);
}
public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'address' => 'required|string|max:255',
        'feasibility_pdf' => 'required|file|mimes:pdf',
        'category_id' => 'required|exists:categories,id',
        'is_active' => 'required|boolean',
    ]);

    // تحميل ملف الـ PDF
    $pdfPath = $request->file('feasibility_pdf')->store('feasibility_pdf', 'public');

    // إنشاء المصنع للمستخدم الحالي
    $factory = factories::create([
        'name' => $request->name,
        'address' => $request->address,
        'feasibility_pdf' => $pdfPath,
        'user_id' => Auth::id(), // من التوكن
        'category_id' => $request->category_id,
        'is_active' => $request->is_active,
        'status' => 'pending',
    ]);

    return response()->json([
        'message' => 'Factory created successfully.',
        'factory' => $factory,
    ], 201);
}

public function getfactorypending()
{
    $factories = factories::with('category', 'user') // تحميل العلاقات إن أردت
        ->where('status', 'pending')
        ->get();

    return response()->json([
        'factories' => $factories
    ]);
}


    
}
