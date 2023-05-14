<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\Product;
use App\Models\TempImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function UploadTemporary(Request $request)
    {
        if ($request->hasFile('images')) {
            $images = $request->file('images');
            $fileNames = $images->getClientOriginalName();
            $folder = uniqid('image-', true);
            $images->storeAs('images/tmp/' . $folder, $fileNames);

            TempImage::create([
                'folder' => $folder,
                'file' => $fileNames,
            ]);
            return $folder;
        }

        return null;
    }
    public function DeleteTemporary()
    {
        $tempImage = TempImage::where('folder', request()->getContent())->first();
        if ($tempImage) {
            Storage::deleteDirectory('images/tmp/' . $tempImage->folder);
            $tempImage->delete();
        }
        return response()->noContent();
    }
    public function StoreProduct(Request $request)
    {
        $valid = Validator::make($request->all(), [
            'title' => ['required'],
            'description' => ['required'],
        ]);
        $tempImages = TempImage::all();
        if ($valid->fails()) {
            foreach ($tempImages as $ti) {
                Storage::deleteDirectory('images/tmp/' . $ti->folder);
                $ti->delete();
            }
            return redirect('/')->withErrors($valid)->withInput();
        }
        $product = Product::create($valid->validated());
        foreach ($tempImages as $ti) {
            Storage::copy('images/tmp/' . $ti->folder . '/' . $ti->file, 'images/' . $ti->folder . '/' . $ti->file);
            Image::create([
                'product_id' => $product->id,
                'name' => $ti->file,
                'path' => $ti->folder . '/' . $ti->file,
            ]);
            Storage::deleteDirectory('images/tmp/' . $ti->folder);
            $ti->delete();
        }
        return redirect('/')->with('success', 'Successfully Added!');
    }
}
