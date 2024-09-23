<?php

namespace App\Http\Controllers;

use App\Models\GuestUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Mail\VerifyEmail;
use Illuminate\Support\Facades\Mail;
use App\Services\GuestUserService;

class GuestUsersController extends Controller
{
    protected $guestUserService;

    public function __construct(GuestUserService $guestUserService)
    {
        $this->guestUserService = $guestUserService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if($request->has('id')){
            $gestUser=GuestUsers::find($request->id);
            if($gestUser){
                $jsonData = [
                    'status' => 'success',
                    'data' => $gestUser,
                ];
            }
            else{
                $jsonData = [
                    'status' => 'error',
                    'message' => 'course not found',
                ];
            }
        }
        else {
            $gestUser = GuestUsers::paginate(5);
            $jsonData = [
                'status' => 'success',
                'data' => $gestUser,
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
            'firstName' => 'required',
            'lastName' => 'required',
            'age' => 'required',
            'email' => 'required|email',
            'timeZone'=>'required'
        ]);
        $guestUser = $this->guestUserService->createGuestUser($request->all());

        return response()->json([
            'message' => 'User created successfully',
            'id' => $guestUser->id,
            'data' => $guestUser
        ]);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request,$id)
    {   
        $request->validate([
            'courseId' => 'required',
            'firstName' => 'required',
            'lastName' => 'required',
            'age' => 'required',
            'email' => 'required|email'
        ]);
            $gestUser=GuestUsers::find($id);

            $gestUser->courseId=$request->courseId;
            $gestUser->firstName=$request->firstName;
            $gestUser->lastName=$request->lastName;
            $gestUser->age=$request->age;
            $gestUser->email=$request->email;
            $gestUser->save();

            return response()->json([
                    'message'=>'success',
                    'data'=>$gestUser
            ]);
    }
    public function verify($token,$courseId,$sessionTimings)
    {
        if ($this->guestUserService->verifyGuestUser($token,$courseId,$sessionTimings)) {
            return response()->json(['message' => 'Email verified successfully.']);
        }

        return response()->json(['message' => 'Invalid or expired verification token.'], 400);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'id'=>'required'
        ]);
        $user =GuestUsers::find($request->id);
        if (!$user) {
            return response()->json(['message' => 'guest user not found'], 404);
        }
        $user->forceDelete();
        return response()->json(['message' => 'guest user deleted']);
    }
}
