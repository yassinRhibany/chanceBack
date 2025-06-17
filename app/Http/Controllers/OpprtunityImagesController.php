<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\factories;
use App\Models\opprtunity_images;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OpprtunityImagesController extends Controller
{
    public function uploadFactoryImage(Request $request, $factoryId)
{
    $request->validate([
        'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    $factory = factories::findOrFail($factoryId);

    $path = $request->file('image')->store('factories', 'public');

    $image = new opprtunity_images();
    $image->factory_id = $factory->id;
    $image->image_path = $path;
    $image->save();

    
    return response()->json([
        'message' => 'Image uploaded successfully',
        'image_path' => $path,
    ]);
}
}
