<?php

namespace App\Http\Controllers;

use Google_Client;
use Google_Service_Calendar;
use Illuminate\Http\Request;

class GoogleOAuthController extends Controller
{
    private function getClient(): Google_Client
    {
        $client = new Google_Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
        $client->setScopes(Google_Service_Calendar::CALENDAR);
        $client->setAccessType('offline');
        $client->setPrompt('consent'); // Force consent to always get refresh_token
        return $client;
    }

    /**
     * Redirect to Google OAuth consent screen
     */
    public function redirect()
    {
        $client = $this->getClient();
        $authUrl = $client->createAuthUrl();
        return redirect($authUrl);
    }

    /**
     * Handle Google OAuth callback — exchange code for tokens
     */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return response()->json(['error' => $request->get('error')], 400);
        }

        $client = $this->getClient();
        $token = $client->fetchAccessTokenWithAuthCode($request->get('code'));

        if (isset($token['error'])) {
            return response()->json(['error' => $token['error'], 'description' => $token['error_description'] ?? ''], 400);
        }

        $accessToken = $token['access_token'];
        $refreshToken = $token['refresh_token'] ?? 'NOT PROVIDED - re-authorize with prompt=consent';

        // Update the .env file with the new tokens
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        $envContent = preg_replace('/GOOGLEACCESSTOKEN=".*"/', 'GOOGLEACCESSTOKEN="' . $accessToken . '"', $envContent);
        $envContent = preg_replace('/GOOGLEREFRESHTOKEN=".*"/', 'GOOGLEREFRESHTOKEN="' . $refreshToken . '"', $envContent);

        file_put_contents($envPath, $envContent);

        return response()->json([
            'message' => '✅ Google OAuth tokens saved to .env!',
            'access_token' => substr($accessToken, 0, 20) . '...',
            'refresh_token' => substr($refreshToken, 0, 20) . '...',
            'note' => 'Restart php artisan serve to pick up the new tokens.',
        ]);
    }
}
