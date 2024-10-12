<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use App\Models\User;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(Request $request, $id, $hash): JsonResponse
    {
        $user = User::find($id);
    
        if (!$user || !hash_equals($hash, $user->verification_token)) {
            return response()->json(['error' => 'Invalid verification link.'], 400);
        }
    
        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.'], 200);
        }
    
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }
    
        return response()->json([
            'message' => 'Email successfully verified.',
            'redirect' => 'https://future-coder.vercel.app'
        ], 200);
    }
    
}
