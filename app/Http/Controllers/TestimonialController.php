<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use Illuminate\Http\Request;
use App\Models\User;

class TestimonialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {   
            if($request->has("id"))
            {
                
                $testimonial = Testimonial::find($request->id);
                if (!$testimonial) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Testimonial not found'
                    ], 404);
                }
            }   
            else
            {
                $testimonial = Testimonial::paginate(5);
            }
        return response()->json(["status"=>"success","data"=> $testimonial]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
            $request->validate([
                "userId"=>'required',
                "description"=>'required',
                "rating"=>'required'
            ]);
            $user=User::find($request->userId);
            if($user)
            {
                $testimonial = Testimonial::create([
                    "userId"=>$user->id,
                    "description"=> $request->description,
                    "rating"=> $request->rating
                ]);
                return response()->json(["status"=>"success","data"=> $testimonial]);
            }
            else
            {
            return response()->json(["status"=> "error","data"=> "user not found"]);
            }

    
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request,$id)
    {
        $testimonial= Testimonial::find($id);
        $testimonial->update([
            "description"=>$request->description,
            "rating"=>$request->rating,
        ]);
        $testimonial->save();
        return response()->json(["status"=> "update successfuly","data"=> $testimonial]);
    }
        public function validTestimonial()
    {
        
        $testimonials = Testimonial::with('user:id,firstName,lastName')
            ->whereHas('user', function ($query) {
                $query->whereNotNull('email_verified_at');
            })
            ->where('is_visible', 1) 
            ->latest() 
            ->limit(10)
            ->get();

        return response()->json($testimonials);
    }

    public function getAllTestimonialsForAdmin()
    {
        $testimonials = Testimonial::with('user:id,email,firstName,lastName')
            ->paginate(6); // عرض 6 عناصر في كل صفحة
    
        // تحويل البيانات إلى صيغة مناسبة لـ JSON مع معلومات التصفح
        $data = [
            'current_page' => $testimonials->currentPage(),
            'last_page' => $testimonials->lastPage(),
            'per_page' => $testimonials->perPage(),
            'total' => $testimonials->total(),
            'data' => $testimonials->items()
        ];
    
        return response()->json($data);
    }

        public function changeVisibility(Request $request)
    {
        $testimonial = Testimonial::findOrFail($request->id);
        $testimonial->is_visible = !$testimonial->is_visible;
        $testimonial->save();

        return response()->json(['message' => 'Visibility status updated successfully.']);
    }

    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        if($request->has('id'))
        {
            $testimonial= Testimonial::find($request->id);
            $testimonial->delete();
            return response()->json(['status'=> 'deleted done']);
        }
        else{
            return response()->json(['status'=> 'error','data'=> 'testimonial not found']);
        }
    }
}
