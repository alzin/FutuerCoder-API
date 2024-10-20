<?php

namespace App\Http\Controllers;
require_once base_path('vendor/autoload.php');


use App\Models\Blogs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Illuminate\Support\Facades\File;

class BlogsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // app/Http/Controllers/BlogController.php

    public function index(Request $request)
    {   
        $translator = new GoogleTranslate();
        $translator->setTarget('ar');

        if ($request->has('id')) {
            $blog = Blogs::find($request->id);
        
            if ($blog) {
                $blog->title = $translator->translate($blog->title);
                $blog->description = $translator->translate($blog->description);
                $jsonData = [
                    'status' => 'success',
                    'data' => $blog,
                ];
            } else {
                $jsonData = [
                    'status' => 'error',
                    'message' => 'blog not found',
                ];
            }
        } else {
            $blog = Blogs::paginate(5);
            foreach ($blog as $bloog) {
                $bloog->title = $translator->translate($bloog->title);
                $bloog->description = $translator->translate($bloog->description);
            }
            $jsonData = [
                'status' => 'success',
                'data' => $blog,
            ];
        }
        
        return response()->json($blog);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'required',
        ]);
        
        /*
        if ($request->hasFile('image')) {
            $directory = 'uploads';
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }
            if (Storage::disk('public')->exists($directory)) {
                echo 'file exists';
            }
            $image = $request->file('image');
        
            $fileName = time() . '.' . $image->getClientOriginalExtension();
            $path = $directory . '/' . $fileName;
            Storage::disk('public')->put($path, file_get_contents($image));
        }
        */
        
        /*
        if ($request->hasFile('image')) {
            // Get the file from the request
            $directory = public_path('images');

            // Create the directory if it doesn't exist
            if (!file_exists($directory)) {
                mkdir($directory, 0775, true);
            }
            // Get the file from the request
            $image = $request->file('image');
            // Define a file name
            $fileName = time() . '.' . $image->getClientOriginalExtension();
            // Save the file to the public folder
            $path = $image->move($directory, $fileName);
        }
        */
        
        /*
        $file_extension = $request->file('image')->getClientOriginalExtension();
        $file_name = time() . '.' . $file_extension;
        $path = 'blogs/' . $file_name;
        Storage::disk('public')->put($path, file_get_contents($request->file('image')));
        */
        
        Blogs::create([
            'title' => $request->title,
            'description' => $request->description,
            'ImagePath' => $request->imagePath,
        ]);
        return response()->json(['message' => 'blog created successfully']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->file('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $ImagePath = $image->storeAs('blogs', $imageName, 'uploads');
            $url = Storage::disk('uploads')->url($ImagePath);

            $blog = new Blogs();
            $blog->title = $request->title;
            $blog->description = $request->description;
            $blog->ImagePath = $url;
            $blog->save();

            return response()->json([
                'success' => 'Image uploaded successfully.',
                'image_name' => $imageName,
                'image_path' => $blog->ImagePath,
            ]);
        }

        return response()->json(['error' => 'Image upload failed.'], 400);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        // Implement logic for showing a specific resource if needed
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request)
    {
        // Implement logic for editing a resource if needed
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {   
        $blog = Blogs::find($id);
        if (!$blog) {
            return response()->json(['message' => 'Blog not found'], 404);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required',
        ]);

        /*
        if ($request->hasFile('image') && $blog->ImagePath) {
            Storage::disk('public')->delete('blogs/' . $blog->ImagePath);
        }

        if ($request->hasFile('image')) {
            $path = 'blogs/' . time() . '.' . $request->image->getClientOriginalExtension();
            Storage::disk('public')->put($path, $request->image);

            $blog->ImagePath = Storage::disk('public')->url($path);
        }
        */

        $blog->ImagePath = $request->imagePath;
        $blog->title = $request->title;
        $blog->description = $request->description;

        $blog->save();
        return response()->json([
            'message' => 'Blog updated successfully',
            'blog' => $blog
        ]);
    }
    //this function use to get the last three blogs from database
    public function getLastThreeBlogs()
{
    $blogs = Blogs::orderBy('created_at', 'desc')->take(3)->get();

    // إعادة البيانات
    return response()->json($blogs);
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $blog = Blogs::find($request->id);
        if (!$blog) {
            return response()->json(['message' => 'Blog not exists'], 404);
        }

        // Delete the blog
        $blog->forceDelete();

        return response()->json(['message' => 'Blog deleted']);
    }
}
