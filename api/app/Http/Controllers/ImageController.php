<?php

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageController extends Controller
{
    public function index()
    {
        $images = Image::all();

        return response()->json([
            'status' => 'success',
            'data' => $images,
        ]);
    }

    public function show($id)
    {
        $image = Image::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $image,
        ]);
    }

    public function storeImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $fileName = Str::uuid()->toString() . '.' . $request->image->extension();

        $path = Storage::disk('s3')->putFileAs('images', $request->image, $fileName, 'public');
        //$path = 'images/' . $fileName;


        $image = Image::create([
            'name' => $fileName,
            'path' => $path,
        ]);

        $image->path = Storage::disk('s3')->url($path);

        return response()->json([
            'status' => 'success',
            'message' => 'Image uploaded successfully',
            'data' => $image,
        ]);
    }

    public function update(Request $request, $id)
    {
        $image = Image::findOrFail($id);

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        Storage::disk('s3')->delete($image->path);

        $fileName = Str::uuid()->toString() . '.' . $request->image->extension();

        $path = Storage::disk('s3')->putFileAs('images', $request->image, $fileName, 'public');

        $image->update([
            'name' => $fileName,
            'path' => $path,
        ]);

        $image->path = Storage::disk('s3')->url($path);

        return response()->json([
            'status' => 'success',
            'message' => 'Image updated successfully',
            'data' => $image,
        ]);
    }

    public function destroy($id)
    {
        $image = Image::findOrFail($id);

        Storage::disk('s3')->delete($image->path);

        $image->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Image deleted successfully',
        ]);
    }
}
