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
            $user=User::find($request->userid);
            if($user)
            {
                $testimonial = Testimonial::create([
                    "userId"=>$user,
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
