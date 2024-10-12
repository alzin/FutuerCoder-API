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
    public function __invoke(Request $request, $id, $hash)
{
    $user = User::find($id);

    if (!$user || !hash_equals($hash, $user->verification_token)) {
        return redirect('https://future-coder.vercel.app')->withErrors(['error' => 'Invalid verification link.']);
    }

    if ($user->hasVerifiedEmail()) {
        return redirect('https://future-coder.vercel.app')->with('message', 'Email already verified.');
    }

    if ($user->markEmailAsVerified()) {
        event(new Verified($user));
    }

    return redirect('https://future-coder.vercel.app')->with('message', 'Email successfully verified.');
}

    
}
