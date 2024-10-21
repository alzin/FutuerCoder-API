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
        $id = $request->input('id');
        $language = $request->input('language');

        $translator = null;
    
        if ($language!=null) {
            $translator = new GoogleTranslate();
            $translator->setTarget($language);
        }
    
        if ($id) {
            $blog = Blogs::find($id);
    
            if ($blog)
             {
                if ($translator) {
                    $blog->title = $translator->translate($blog->title);
                    $blog->description = $translator->translate($blog->description);
                }
    
                $jsonData = [
                    'status' => 'success',
                    'data' => $blog,
                ];
                } 
                else 
                {
                    $jsonData = [
                        'status' => 'error',
                        'message' => 'Blog not found',
                    ];
                }
    
            return response()->json($jsonData);
        }
        else
        {
            $blogs = Blogs::paginate(5);
        
            if ($translator) {
                foreach ($blogs as $blog) {
                    
                    if (!empty($blog->title)) {
                        $blog->title = $translator->translate($blog->title);
                    }
                    if (!empty($blog->description)) {
                        $blog->description = $translator->translate($blog->description);
                    }
                }
            }
        
            $jsonData = [
                'status' => 'success',
                'data' => $blogs,
            ];
        
            return response()->json($jsonData);
        }
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

        $translator = new GoogleTranslate();
        $translator->setTarget('en'); 

        $translatedTitle = $translator->translate($request->title);
        $translatedDescription = $translator->translate($request->description);

        Blogs::create([
            'title' => $translatedTitle,
            'description' => $translatedDescription,
            'ImagePath' => $request->imagePath,
        ]);

        return response()->json(['message' => 'Blog created successfully']);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

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
    
        $translator = new GoogleTranslate();
        $translator->setTarget('en'); 
        
        $translatedTitle = $translator->translate($request->title);
        $translatedDescription = $translator->translate($request->description);
    
        $blog->ImagePath = $request->imagePath;
        $blog->title = $translatedTitle;
        $blog->description = $translatedDescription;
    
        $blog->save();
    
        return response()->json([
            'message' => 'Blog updated successfully',
            'blog' => $blog
        ]);
    }
    
    //this function use to get the last three blogs from database
        public function getLastThreeBlogs(Request $request)
    {   $language=$request->input('language');
        $blogs = Blogs::orderBy('created_at', 'desc')->take(3)->get();

        if ($language) {
            
            $translator = new GoogleTranslate();
            $translator->setTarget($language); 
            
            foreach ($blogs as $blog) {
                $blog->title = $translator->translate($blog->title);
                $blog->description = $translator->translate($blog->description);
            }
        }

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
