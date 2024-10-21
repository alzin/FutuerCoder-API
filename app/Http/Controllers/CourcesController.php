<?php

namespace App\Http\Controllers;

use App\Models\Cources;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Stichoza\GoogleTranslate\GoogleTranslate;


class CourcesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {   
       
        $language = $request->input('language');
        $translator = null;

        if ($language) {
          
            $translator = new GoogleTranslate();
            $translator->setTarget($language); 
        }

        $id = $request->input('id');
        
        if ($id) {
            $course = Cources::find($id);

            if ($course) {
               
                if ($translator) {
                    $course->title = $translator->translate($course->title);
                    $course->description = $translator->translate($course->description);
                }

                $jsonData = [
                    'status' => 'success',
                    'data' => $course,
                ];
            } else {
                $jsonData = [
                    'status' => 'error',
                    'message' => 'course not found',
                ];
            }
        } else {
            $courses = Cources::paginate(5);

            if ($translator) {
                foreach ($courses as $course) {
                    $course->title = $translator->translate($course->title);
                    $course->description = $translator->translate($course->description);
                }
            }

            $jsonData = [
                'status' => 'success',
                'data' => $courses,
            ];
        }

        return response()->json($jsonData);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {   
        $translator = new GoogleTranslate();
        $translator->setTarget('en');
        $request->validate([
            'title' => 'required',
            'description' => 'required',
            'teacher' => 'required',
            'imagePath' => 'required',
            'price' => 'required',
            'course_outline' => 'required',
            'duration_in_session' => 'required',
            'course_start_date' => 'required',
            'min_age' => 'required',
            'max_age' => 'required',
            
        ]);
        
        $course = Cources::create([
            'title' => $translator->translate($request->title),
            'teacher' => $translator->translate($request->teacher),
            'description' => $translator->translate($request->description),
            'imagePath'=>$request->imagePath,
            'price'=>$request->price,
            'course_outline'=>$translator->translate($request->course_outline),
            'duration_in_session'=>$request->duration_in_session,
            'course_start_date'=>$request->course_start_date,
            'min_age'=>$request->min_age,
            'max_age'=>$request->max_age,
            'payment_url'=>$request->payment_url
            
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
    public function update(Request $request)
    {   
        $translator = new GoogleTranslate();
        $translator->setTarget('en');

        $course = Cources::find($request->id);
        if (!$course) {
            return response()->json(['message' => 'course not found'], 404);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required',
            'teacher' => 'required',
            'imagePath' => 'required',
            'price' => 'required',
            'course_outline' => 'required',
            'duration_in_session' => 'required',
            'course_start_date' => 'required',
            'min_age' => 'required',
            'max_age' => 'required',
        ]);
        $course->imagePath = $request->imagePath;
        $course->title = $translator->translate($request->title);
        $course->teacher=$translator->translate($request->teacher);
        $course->description =$translator->translate($request->description);
        $course->price=$request->price;
        $course->course_outline=$translator->translate($request->course_outline);
        $course->duration_in_session=$request->duration_in_session;
        $course->course_start_date=$request->course_start_date;
        $course->min_age=$request->min_age;
        $course->max_age=$request->max_age;
        $course->payment_url=$request->payment_url;
    
        $course->save();
        return response()->json([
            'message' => 'course updated',
            'course : ' => $course
        ]);
    }
      /**
     * get course by the student Age
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function getCoursesByAge(Request $request)
    {
        $age = $request->input('age');
        $language=$request->input('language');
        
        
        if ($language) {
            $translator = new GoogleTranslate();
            $translator->setTarget($language);
    
        }
        $courses = Cources::where('min_age', '<=', $age)
                             ->where('max_age', '>=', $age)
                             ->get();
        
        
        if ($language) {
            foreach ($courses as $course) {
                $course->title = $translator->translate($course->title);
                $course->description = $translator->translate($course->description);
            }
        }
    
        
        return response()->json($courses);
        
    }
    

        public function getCourseHaveTime(Request $request)
    {
    
        $timezone = $request->input('timezone', config('app.timezone'));
        if ($request->has('language')) {
            $translator = new GoogleTranslate();
            $translator->setTarget($request->input('language')); 
        }
        $currentDateTime = Carbon::now($timezone);

        $coursesWithTimes = Cources::whereHas('cources_times', function ($query) use ($currentDateTime, $timezone) {
            $query->where(function($query) use ($currentDateTime, $timezone) {
                $query->whereRaw("DATE_ADD(SessionTimings, INTERVAL TIME_TO_SEC(CONVERT_TZ(startTime, '+00:00', ?)) SECOND) > ?", [$timezone, $currentDateTime->toDateTimeString()])
                    ->orWhere(function($query) use ($currentDateTime, $timezone) {
                        $query->whereRaw("DATE_ADD(SessionTimings, INTERVAL TIME_TO_SEC(CONVERT_TZ(endTime, '+00:00', ?)) SECOND) > ?", [$timezone, $currentDateTime->toDateTimeString()]);
                    });
            });
        })
        ->paginate(10, ['id', 'title', 'teacher', 'description', 'price', 'min_age', 'max_age', 'imagePath']);

        if ($coursesWithTimes->isEmpty()) {
            return response()->json(['message' => 'No courses with upcoming times found'], 404);
        }
        if ($request->has('language')) {
            foreach ($coursesWithTimes as $course) {
                $course->title = $translator->translate($course->title);
                $course->description = $translator->translate($course->description);
            }
        }

        return response()->json([
            "message" => "successful",
            "data" => $coursesWithTimes->items(),
            "current_page" => $coursesWithTimes->currentPage(),
            "last_page" => $coursesWithTimes->lastPage(),
            "per_page" => $coursesWithTimes->perPage(),
            "total" => $coursesWithTimes->total()
        ]);
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