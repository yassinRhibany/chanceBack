<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\categories;

class CategoriesController extends Controller
{
    public function index()
    {
        $categories = categories::all();
        return response()->json($categories);
    }

     
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $category = categories::create([
            'name' => $request->name
        ]);

        return response()->json($category, 201);
    }

    
    public function destroy($id)
    {
        $category = categories::findOrFail($id);
        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }

}
