<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use Illuminate\Http\Request;
use App\Models\User;
use Stichoza\GoogleTranslate\GoogleTranslate;

class TestimonialController extends Controller
{
    /**
     * Display a listing of the resource.
     */

     public function index(Request $request)
     {
        
         if ($request->has('language')) {
             $translator = new GoogleTranslate();
             $translator->setTarget($request->input('language')); 
         }
     
        
         if ($request->has("id")) {
             $testimonial = Testimonial::find($request->id);
     
             if (!$testimonial) {
                 return response()->json([
                     'status' => 'error',
                     'message' => 'Testimonial not found'
                 ], 404);
             }
     
             if ($request->has('language')) {
                 $testimonial->description = $translator->translate($testimonial->description);
             }
     
             return response()->json([
                 "status" => "success",
                 "data" => $testimonial
             ]);
         } 
         else {
             $testimonials = Testimonial::paginate(5);
     
             if ($request->has('language')) {
                 foreach ($testimonials as $testimonial) {
                     $testimonial->description = $translator->translate($testimonial->description);
                 }
             }
             return response()->json([
                 "status" => "success",
                 "data" => $testimonials
             ]);
         }
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
                $translator = new GoogleTranslate();
                $translator->setTarget('en');
                $testimonial = Testimonial::create([
                    "userId"=>$user->id,
                    "description"=>$translator->translate($request->description),
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
        $translator = new GoogleTranslate();
        $translator->setTarget('en');
        $testimonial->update([
            "description"=>$translator->translate($request->description),
            "rating"=>$request->rating,
        ]);
        $testimonial->save();
        return response()->json(["status"=> "update successfuly","data"=> $testimonial]);
    }
        public function validTestimonial(Request $request)
    {
        $language = $request->input('language');
    
        $translator = new GoogleTranslate();
        $translator->setTarget($language);
        
        $testimonials = Testimonial::with('user:id,firstName,lastName')
            ->whereHas('user', function ($query) {
                $query->whereNotNull('email_verified_at');
            })
            ->where('is_visible', 1) 
            ->latest() 
            ->limit(10)
            ->get();
            foreach ($testimonials as $testimonial) {
                $testimonial->description = $translator->translate($testimonial->description);
                $testimonial->user->firstName = $translator->translate($testimonial->user->firstName);
                $testimonial->user->lastName = $translator->translate($testimonial->user->lastName);
            }
        
        return response()->json($testimonials);
    }

    public function getAllTestimonialsForAdmin(Request $request)
    {   
        $language = $request->input('language');
        $translator = new GoogleTranslate();
        $translator->setTarget($language);
        $testimonials = Testimonial::with('user:id,email,firstName,lastName')
            ->paginate(6); 
            foreach ($testimonials as $testimonial) {
                $testimonial->description = $translator->translate($testimonial->description);
                $testimonial->user->firstName = $translator->translate($testimonial->user->firstName);
                $testimonial->user->lastName = $translator->translate($testimonial->user->lastName);
            }
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
