<?php

namespace App\Http\Controllers;

use App\Models\Cources;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class CourcesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->has('id')) 
        {
            
            $course = Cources::find($request->id);
        
            if ($course) {
                $jsonData = [
                    'status' => 'success',
                    'data' => $course,
                ];
            } 
            else {
                $jsonData = [
                    'status' => 'error',
                    'message' => 'course not found',
                ];
            }
        }
        else 
        {
            $courses = Cources::paginate(5);
            $jsonData = [
                'status' => 'success',
                'data' => $courses,
            ];   
        }
        return response()->json([$jsonData]);
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
        $file_extintion = $request->image->getClientOriginalExtension();
        $file_name = time() . '.' . $file_extintion;
        $path = 'images/courses/' . $file_name;   
        Storage::disk('public')->put($path, $request->image);
        $imageUrl = asset($path); 
        */
        $course = Cources::create([
            'title' => $request->title,
            'teacher' => $request->teacher,
            'description' => $request->description,
            'imagePath'=>$request->imagePath,
            'price'=>$request->price,
            'course_outline'=>$request->course_outline,
            'duration_in_session'=>$request->duration_in_session,
            'course_start_date'=>$request->course_start_date,
            'min_age'=>$request->min_age,
            'max_age'=>$request->max_age,
            
        ]);
        return response()->json([
                                'message' => 'Course created successfully'
                                ,'data'=>$course
                                ]);
    } 

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Cources $cources)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cources $cources)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request,$id)
    {   
        
        $course = Cources::find($id);
        if (!$course) {
            return response()->json(['message' => 'course not found'], 404);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required',
        ]);
        /*
        if ($request->hasFile('image')) {
            // حذف الصورة القديمة إذا وجدت
            if ($course->image) {
                $oldImagePath = public_path('images/courses/' . $course->image);
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                    Storage::delete($oldImagePath);
                    $course->image = null;
                    $course->save();
                }
            }
        }
            // حفظ الصورة الجديدة
        $file_extintion = $request->image->getClientOriginalExtension();
        $file_name = time() . '.' . $file_extintion;
        $path = 'images/courses';
        Storage::disk('public')->put($path, $request->image);
        */
            // تحديث مسار الصورة في قاعدة البيانات
        $course->imagePath = $request->imagePath;
        $course->title = $request->title;
        $course->teacher=$request->teacher;
        $course->description = $request->description;
        $course->price=$request->price;
        $course->course_outline=$request->course_outline;
        $course->duration_in_session=$request->duration_in_session;
        $course->course_start_date=$request->course_start_date;
        $course->min_age=$request->min_age;
        $course->max_age=$request->max_age;
    
        $course->save();
        // إرجاع استجابة JSON تحتوي على البيانات المحدثة للمدونة
        return response()->json([
            'message' => 'course updated',
            'course : ' => $course
        ]);
    }
      /**
     * الحصول على الكورسات بناءً على العمر المدخل.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCoursesByAge(Request $request)
    {
        // الحصول على العمر من طلب المستخدم
        $age = $request->input('age');
     
        $courses = Cources::where('min_age', '<=', $age)
                         ->where('max_age', '>=', $age)
                         ->get();
        // إرجاع النتيجة على شكل JSON
        return response()->json($courses);
    }
    /**
     * Remove the specified resource from storage.
     */
   public function destroy(Request $request)
{
    $course = Cources::find($request->id);
    if (!$course) {
        return response()->json(['message' => 'course not found'], 404);
    }

    $imagePath = $course->image;
    /*
    if (Storage::disk('public')->exists($imagePath)) {
        Storage::disk('public')->delete($imagePath);
    }*/

    $course->forceDelete();
    return response()->json(['message' => 'course deleted']);
}
}