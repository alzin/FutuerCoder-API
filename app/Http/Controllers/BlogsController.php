<?php

namespace App\Http\Controllers;

use App\Models\Blogs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class BlogsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   // app/Http/Controllers/BlogController.php

    public function index(Request $request)
        {   
            if ($request->has('id')) {
                // إذا كان هناك قيمة لـ id
                $blog = Blogs::find($request->id);
            
                if ($blog) {
                    // إذا وجدت المدونة
                    $jsonData = [
                        'status' => 'success',
                        'data' => $blog,
                    ];
                } else {
                    // إذا لم توجد المدونة
                    $jsonData = [
                        'status' => 'error',
                        'message' => 'المدونة غير موجودة',
                    ];
                }
            } else {
                // إذا لم يتم تمرير أي قيمة لـ id
                $blog = Blogs::paginate(5);
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
            // تحديد المسار النسبي داخل مجلد التخزين
            $directory = 'uploads';
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }
            if (Storage::disk('public')->exists($directory)) {
                echo 'file exists';
            }
            // الحصول على الملف من الطلب
            $image = $request->file('image');
        
            // تحديد اسم الملف
            $fileName = time() . '.' . $image->getClientOriginalExtension();
            // إنشاء المسار الكامل داخل مجلد التخزين
            $path = $directory . '/' . $fileName;
            // حفظ الصورة في المجلد المحدد داخل التخزين باستخدام Storage facade
            Storage::disk('public')->put($path, file_get_contents($image));}
        /*
        if ($request->hasFile('image')) {
            // Get the file from the request
            $directory = public_path('images');

            // Create the directory if it doesn't exist
            if (!file_exists($directory)) {
                mkdir($directory, 0775, true);
            // Get the file from the request
            $image = $request->file('image');
            // Define a file name
            $fileName = time() . '.' . $image->getClientOriginalExtension();
            // Save the file to the public folder
            $path = $image->move($directory, $fileName);
        }
            */
        /*$file_extension = $request->file('image')->getClientOriginalExtension();
        $file_name = time() . '.' . $file_extension;
        $path = 'blogs/' . $file_name;
        // استخدام Storage facade لتخزين الصورة
        Storage::disk('public')->put($path, file_get_contents($request->file('image')));
        */
        
        Blogs::create([
            'title' => $request->title,
            'description' => $request->description,
            'ImagePath' => $request->imagePath,
        ]);
        return response()->json(['message' => 'blog createc successfully']);
    }



    

    /**
     * Store a newly created resource in storage.
     */


     public function store(Request $request)
     {
         // التحقق من صحة الطلب
         $request->validate([
             'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
         ]);
 
         // رفع الصورة وتخزينها في المسار المحدد
         if ($request->file('image')) {
             $image = $request->file('image');
             $imageName = time() . '.' . $image->getClientOriginalExtension();
             $ImagePath = $image->storeAs('blogs', $imageName, 'uploads');
             $url = Storage::disk('uploads')->url($ImagePath);
 
             // حفظ بيانات الصورة في قاعدة البيانات
             $blog = new Blogs();
             $blog->title = $request->title;
             $blog->description= $request->description;
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
    public function show(Request $reques)
    {
        
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request)
    {
        
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request,$id)
    {   
        $blog = Blogs::find($id);
        if (!$blog) {
            return response()->json(['message' => 'لم يتم العثور على المدونة'], 404);
        }

        // التحقق من صحة البيانات الواردة
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required',
        ]);
        /*
        if ($request->hasFile('image') && $blog->ImagePath) {
            Storage::disk('public')->delete('blogs/' . $blog->ImagePath);
        }

        // حفظ الصورة الجديدة (إذا تم تحميل صورة جديدة)
        if ($request->hasFile('image')) {
            $path = 'blogs/' . time() . '.' . $request->image->getClientOriginalExtension();
            Storage::disk('public')->put($path, $request->image);
    
            // تحديث مسار الصورة في قاعدة البيانات
        $blog->ImagePath =Storage::disk('public')->url($path);
        }
        */
        $blog->ImagePath =$request->imagePath;
        $blog->title = $request->title;
        $blog->description = $request->description;
    
        $blog->save();
        // إرجاع استجابة JSON تحتوي على البيانات المحدثة للمدونة
        return response()->json([
            'message' => 'تم تحديث المدونة بنجاح',
            'blog' => $blog
        ]);
    }



        /**
         * Remove the specified resource from storage.
         */
        public function destroy(Request $request)
        {
            // التحقق من وجود المدونة
            $blog = Blogs::find($request->id);
            if (!$blog) {
                return response()->json(['message' => 'المدونة غير موجودة'], 404);
            }

            // حذف المدونة
            $blog->forceDelete();

            return response()->json(['message' => 'تم حذف المدونة بنجاح']);
        }
}