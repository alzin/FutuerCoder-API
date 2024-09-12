<?php

namespace App\Http\Controllers;

use App\Models\subscribers;
use Illuminate\Http\Request;
use App\Mail\VerifyEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SubscribersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {   if($request->has('id'))
        {
            $sub=subscribers::find($request->id);
            if($sub){
                $jsonData = [
                    'status' => 'success',
                    'data' => $sub,
                ];
            }
            else {
                // إذا لم توجد البيانات
                $jsonData = [
                    'status' => 'error',
                    'message' => 'البيانات غير موجودة',
                ];
                }
           
        }
        else{
                // إذا لم يتم تمرير أي قيمة لـ id
                $sub=subscribers::paginate(10);
                $jsonData = [
                    'status' => 'success',
                    'data' => $sub,
                ];
            }
            return response()->json($sub);
    }


    public function create(Request $request)
    {
        // قواعد التحقق
        $rules = [
            'email' => 'required|email|unique:subscribers',
        ];
    
        // التحقق من صحة البيانات
        $validated = $request->validate($rules);
    
        if ($validated) {
            // إنشاء المستخدم
            $subscriber = subscribers::create($validated);
    
            // توليد رمز التحقق
            $verificationToken = Str::random(32);
    
            // حفظ رمز التحقق في قاعدة البيانات
            $subscriber->verification_token = $verificationToken;
            $subscriber->save();
    
            // إعداد رابط التحقق
            $verificationUrl = url("/api/verify-subscriber-email/{$verificationToken}");
    
            // إرسال البريد الإلكتروني
            Mail::to($subscriber->email)->send(new VerifyEmail($verificationUrl));
    
            return response()->json(['message' => 'Subscriber created successfully. Please check your email to verify your account.']);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store()
    {   
        
    }

    /**
     * Display the specified resource.
     */
    public function show(subscribers $subscribers)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(subscribers $subscribers)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request,$id)
    {   $request->validate(['email' => 'required',]);
        
            $sub=subscribers::find($id);
            if(!$sub){
                return response()->json(['message'=>'the subscriber not found']);
            }
        else{
            $sub->email=
            $request->email;
            $sub->save();
            return response()->json(['message'=>'update done','sub'=>$sub]);
            }
            
    }
    public function verify($token)
    {
        // البحث عن المشترك باستخدام رمز التحقق
        $subscriber = subscribers::where('verification_token', $token)->first();

        if ($subscriber) {
            // تأكيد البريد الإلكتروني
            $subscriber->update([
                'email_verified_at' => now(),
                'verification_token' => null,
                'email_verified'=>1
            ]);
            $subscriber->save();

            return response()->json(['message' => 'Email verified successfully.']);
        }
        else{
            return response()->json(['message' => 'sorry Invalid or expired verification token.'], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $sub=subscribers::find($request->id);
        if (!$sub) {
            return response()->json(['message' => 'the subscriber not exsist'], 404);
        }

        // حذف المدونة
        $sub->forceDelete();

        return response()->json(['message' => 'deleted done']);
    }
}