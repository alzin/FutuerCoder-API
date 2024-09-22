<?php

namespace App\Http\Controllers;

use App\Models\subscribers;
use Illuminate\Http\Request;
use App\Mail\VerifyEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Services\SubscriberService;
class SubscribersController extends Controller
{
    protected $subscriberService;

    public function __construct(SubscriberService $subscriberService)
    {
        $this->subscriberService = $subscriberService;
    }

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
                $jsonData = [
                    'status' => 'error',
                    'message' => 'Data not found',
                ];
                }
           
        }
        else{
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
        $rules = [
            'email' => 'required|email|unique:subscribers',
        ];
        $validated = $request->validate($rules);
    
        if ($validated) {
            $subscriber = $this->subscriberService->createSubscriber($validated);
            return response()->json(['message' => 'Subscriber created successfully. Please check your email to verify your account.']);
        }
    }
    //this function reffered to validate subscriber service
    public function verify($token)
    {
        if ($this->subscriberService->verifySubscriber($token)) {
            return response()->json(['message' => 'Email verified successfully.']);
        }

        return response()->json(['message' => 'Invalid or expired verification token.'], 400);
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
        $sub->forceDelete();

        return response()->json(['message' => 'deleted done']);
    }
}