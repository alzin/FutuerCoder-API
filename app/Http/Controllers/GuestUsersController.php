<?php

namespace App\Http\Controllers;

use App\Models\GuestUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Mail\VerifyEmail;
use Illuminate\Support\Facades\Mail;

class GuestUsersController extends Controller
{
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
        $verificationToken = Str::random(32);
        
        $guestUser=GuestUsers::create([
            'firstName' => $request->firstName,
            'lastName' => $request->lastName,
            'age' => $request->age,
            'email' => $request->email,
            'timeZone'=>$request->timeZone,
            'verification_token' => $verificationToken
        ]);
          // إعداد رابط التحقق
          $verificationUrl = url("/api/verify-email/{$verificationToken}");
    
          // إرسال البريد الإلكتروني
          Mail::to($guestUser->email)->send(new VerifyEmail($verificationUrl));
        
        return response()->json([
            'message' => 'user created successfully',
            'id'=>$guestUser->id,
            'data'=>$guestUser
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
    public function show(GuestUsers $guestUsers)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(GuestUsers $guestUsers)
    {
        //
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
    public function verify($token)
    {
        // البحث عن المستخدم الضيف باستخدام رمز التحقق
        $guestUser = GuestUsers::where('verification_token', $token)->first();

        if ($guestUser) {
            // تأكيد البريد الإلكتروني
            $guestUser->update([
                'email_verified_at' => now(),
                'verification_token' => null,
                'email_verified' => 1
            ]);
            $guestUser->save();

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
