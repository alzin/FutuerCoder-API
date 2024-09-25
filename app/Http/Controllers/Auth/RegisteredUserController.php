<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
        public function store(Request $request)
    {
        $validatedData = $request->validate([
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'age' => 'required|integer|min:0',
            'timeZone' => 'required',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $verificationToken = Str::random(64);

        $user = User::create([
            'firstName' => $validatedData['firstName'],
            'lastName' => $validatedData['lastName'],
            'age' => $validatedData['age'],
            'timeZone' => $validatedData['timeZone'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'verification_token' => $verificationToken,
        ]);

        $user->save();

        // Send verification email with token and user ID
        $user->sendEmailVerificationNotification($verificationToken, $user->id);

        $token = $user->createToken('auth_token')->plainTextToken;

        event(new Registered($user));

        return response()->json(['message' => 'User registered successfully', 'token' => $verificationToken], 201);
    }

    public function login(Request $request)
    {
        // التحقق من البيانات المدخلة
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        // التحقق من صحة بيانات الاعتماد
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['بيانات الاعتماد غير صحيحة.'],
            ]);
        }

        // التحقق من أن البريد الإلكتروني مفعل (إذا كنت تستخدم التحقق)
        if (!$user->hasVerifiedEmail()) {
            return response()->json(['message' => 'لم يتم التحقق من البريد الإلكتروني.'], 403);
        }

        // إنشاء رمز مميز (Token) للمستخدم
        $token = $user->createToken('auth_token')->plainTextToken;

        // إعادة البيانات في الاستجابة
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $user,
        ], 200);
    }

    // دالة تسجيل الخروج
    public function logout(Request $request)
    {
        // حذف الرمز المميز الحالي
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'تم تسجيل الخروج بنجاح.',
        ]);
    }

}
