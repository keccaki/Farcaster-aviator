<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
use App\Models\MagicLoginToken;
use App\Mail\MagicLoginLink;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;

class Authentication extends Controller
{
    public function login(Request $r)
    {
        $validated = $r->validate([
            'username' => 'required',
            'body' => 'password',
        ]);
        $data = "";
        $isSuccess = false;
        $message = "";
        $usernameexist = User::where('mobile', $r->username)->orWhere('email', $r->username)->first();
        if ($usernameexist) {
            if (Hash::check($r->password, $usernameexist->password)) {
                $r->session()->put('userlogin', $usernameexist);
                $message = "";
                $isSuccess = true;
            } else {
                $message = "Incorrect Password!";
            }
        } else {
            $message = "Username not found!";
        }
        $res = array("data" => $data, "isSuccess" => $isSuccess, "message" => $message);
        return response()->json($res);
    }

    public function register(Request $r)
    {
        $validated = $r->validate([
            'name' => 'required',
            'gender' => 'required',
            'email' => 'required',
            'password' => 'required'
        ]);
        $data = "";
        $isSuccess = false;
        $message = "Something wen't wrong!";
        $promocode = '';
        if ($r->promocode != '') {
            $existpromocode = User::where('id', $r->promocode)->first();
            if ($existpromocode) {
                $olddata = User::where('email', $r->email)->orWhere('mobile', $r->mobile)->get();
                if (count($olddata) > 0) {
                    $message = "Dublicate Email Id/Mobile No., Please enter Unique Email id";
                } else {
                    $wallet = new Wallet;
                    $user = new User;
                    $user->name = $r->name;
					$user->image = "/images/avtar/av-".rand(1,72).".png";
                    $user->mobile = $r->mobile;
                    $user->email = $r->email;
                    $user->password = Hash::make($r->password);
                    $user->currency = '$';
                    $user->gender = $r->gender;
                    $user->country = 'IN';
                    $user->status = '1';
                    $user->promocode = $r->promocode;
                    if ($user->save()) {
                        $afterregisterdata = User::where('email', $r->email)->orderBy('id', 'desc')->first();
                        if ($afterregisterdata) {
                            $wallet->userid = $afterregisterdata->id;
                            $wallet->amount = setting('initial_bonus');
                            if ($wallet->save()) {
                                $data = array("username" => $afterregisterdata->email, "password" => $r->password, "token" => csrf_token());
                                $isSuccess = true;
                            }
                        }
                    }
                }
            }else{
                $data = array();
                $message = "Invalid Promocode";
            }
        } else {
            $olddata = User::where('email', $r->email)->orWhere('mobile', $r->mobile)->get();
            if (count($olddata) > 0) {
                $message = "Dublicate Email Id/Mobile No., Please enter Unique Email id";
            } else {
                $wallet = new Wallet;
                $user = new User;
                $user->name = $r->name;
                $user->mobile = $r->mobile;
                $user->email = $r->email;
                $user->password = Hash::make($r->password);
                $user->currency = '$';
                $user->gender = $r->gender;
                $user->country = 'IN';
                $user->status = '1';
                $user->promocode = $r->promocode;
                if ($user->save()) {
                    $afterregisterdata = User::where('email', $r->email)->orderBy('id', 'desc')->first();
                    if ($afterregisterdata) {
                        $wallet->userid = $afterregisterdata->id;
                        $wallet->amount = setting('initial_bonus');
                        if ($wallet->save()) {
                            $data = array("username" => $afterregisterdata->email, "password" => $r->password, "token" => csrf_token());
                            $isSuccess = true;
                        }
                    }
                }
            }
        }
        $res = array("data" => $data, "isSuccess" => $isSuccess, "message" => $message);
        return response()->json($res);
    }

    public function adminlogin(Request $r)
    {
        $validated = $r->validate([
            'username' => 'required',
            'password' => 'required',
        ]);
        $response = array('status' => 0, 'title' => "Oops!!", 'message' => "Invalid Credential!");
        $usernameexist = User::where('mobile', $r->username)->orWhere('email', $r->username)->where('isadmin', '1')->first();
        if ($usernameexist) {
            if (Hash::check($r->password, $usernameexist->password)) {
                $r->session()->put('adminlogin', $usernameexist);
                $response = array('status' => 1, 'title' => "Success!!", 'message' => "Login Successfully!");
            } else {
                $response = array('status' => 0, 'title' => "Oops!!", 'message' => "Incorrect Password!");
            }
        } else {
            $response = array('status' => 0, 'title' => "Oops!!", 'message' => "Username not exists!");
        }
        return response()->json($response);
    }

    /**
     * Farcaster authentication - login/register Farcaster users
     * 
     * @param Request $r
     * @return \Illuminate\Http\JsonResponse
     */
    public function farcasterLogin(Request $r)
    {
        $validated = $r->validate([
            'fid' => 'required|integer',
            'username' => 'required|string',
            'displayName' => 'string|nullable',
        ]);

        $fid = $r->fid;
        $username = $r->username;
        $displayName = $r->displayName ?? $username;

        try {
            // Check if Farcaster user already exists
            $user = User::where('farcaster_id', $fid)->first();
            
            if (!$user) {
                // Create new user for Farcaster
                $user = new User;
                $user->name = $displayName;
                $user->farcaster_id = $fid;
                $user->farcaster_username = $username;
                $user->email = $fid . '@farcaster.local'; // Placeholder email
                $user->password = Hash::make(Str::random(32)); // Random password
                $user->currency = '$';
                $user->gender = 'other'; // Default
                $user->country = 'FC'; // Farcaster
                $user->status = '1';
                $user->image = "/images/avtar/av-".rand(1,72).".png";
                
                if ($user->save()) {
                    // Create wallet for new user using existing system
                    $wallet = new Wallet;
                    $wallet->userid = $user->id;
                    $wallet->amount = setting('initial_bonus') ?? 25.0; // $25 welcome bonus
                    $wallet->save();

                    // Log registration
                    Log::info('New Farcaster user registered', [
                        'fid' => $fid,
                        'username' => $username,
                        'user_id' => $user->id
                    ]);
                }
            } else {
                // Update existing user's Farcaster info if changed
                if ($user->farcaster_username !== $username || $user->name !== $displayName) {
                    $user->farcaster_username = $username;
                    $user->name = $displayName;
                    $user->save();
                }
            }

            // Set session using existing login system
            $r->session()->put('userlogin', $user);

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'username' => $user->name,
                    'farcaster_id' => $user->farcaster_id,
                    'balance' => Wallet::where('userid', $user->id)->first()->amount ?? 0,
                    'is_new_user' => !User::where('farcaster_id', $fid)->exists()
                ],
                'message' => 'Farcaster authentication successful'
            ]);

        } catch (Exception $e) {
            Log::error('Farcaster authentication failed', [
                'fid' => $fid,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Authentication failed'
            ], 500);
        }
    }

    /**
     * Send magic login link via email
     * 
     * @param Request $r
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMagicLink(Request $r)
    {
        $validated = $r->validate([
            'email' => 'required|email',
        ]);

        $email = $r->email;
        $ipAddress = $r->ip();
        $userAgent = $r->userAgent();

        // Rate limiting: Max 3 requests per 5 minutes per IP
        $rateLimitKey = 'magic-link:' . $ipAddress;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            return response()->json([
                'isSuccess' => false,
                'message' => "Too many requests. Please try again in {$seconds} seconds."
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, 300); // 5 minutes

        // Check if user exists
        $user = User::where('email', $email)->first();
        if (!$user) {
            // For security, don't reveal if email exists or not
            Log::warning("Magic link requested for non-existent email: {$email}", [
                'ip' => $ipAddress,
                'user_agent' => $userAgent
            ]);
            
            return response()->json([
                'isSuccess' => true,
                'message' => 'If this email is registered, you will receive a magic login link shortly.'
            ]);
        }

        // Check if user account is active
        if ($user->status !== '1') {
            Log::warning("Magic link requested for inactive account: {$email}", [
                'ip' => $ipAddress,
                'user_agent' => $userAgent
            ]);
            
            return response()->json([
                'isSuccess' => false,
                'message' => 'Account is not active. Please contact support.'
            ]);
        }

        try {
            // Generate magic login token
            $magicToken = MagicLoginToken::generateToken($email, $ipAddress, $userAgent);

            // Send email
            Mail::to($email)->send(new MagicLoginLink($magicToken));

            Log::info("Magic link sent successfully", [
                'email' => $email,
                'token_id' => $magicToken->id,
                'ip' => $ipAddress
            ]);

            return response()->json([
                'isSuccess' => true,
                'message' => 'Magic login link sent to your email. Check your inbox!'
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send magic link", [
                'email' => $email,
                'error' => $e->getMessage(),
                'ip' => $ipAddress
            ]);

            return response()->json([
                'isSuccess' => false,
                'message' => 'Failed to send magic link. Please try again or contact support.'
            ], 500);
        }
    }

    /**
     * Verify magic login token and log user in
     * 
     * @param Request $r
     * @return \Illuminate\Http\RedirectResponse
     */
    public function magicLogin(Request $r)
    {
        $token = $r->query('token');
        
        if (!$token) {
            return redirect('/')->with('error', 'Invalid magic link.');
        }

        // Verify token and get user
        $user = MagicLoginToken::verifyToken($token);
        
        if (!$user) {
            Log::warning("Invalid or expired magic link attempted", [
                'token' => substr($token, 0, 10) . '...',
                'ip' => $r->ip()
            ]);
            
            return redirect('/')->with('error', 'Magic link is invalid or has expired. Please request a new one.');
        }

        // Check if user account is still active
        if ($user->status !== '1') {
            Log::warning("Magic login attempted for inactive account", [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $r->ip()
            ]);
            
            return redirect('/')->with('error', 'Account is not active. Please contact support.');
        }

        // Log successful magic login
        Log::info("Successful magic login", [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $r->ip()
        ]);

        // Create session (same as regular login)
        $r->session()->put('userlogin', $user);

        // Redirect to dashboard with success message
        return redirect('/dashboard')->with('success', 'Welcome back! You have been logged in successfully.');
    }

    /**
     * Check magic link status (for frontend polling)
     * 
     * @param Request $r
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkMagicLinkStatus(Request $r)
    {
        $token = $r->query('token');
        
        if (!$token) {
            return response()->json([
                'isValid' => false,
                'message' => 'No token provided'
            ]);
        }

        $isValid = MagicLoginToken::isValidToken($token);
        $expiryMinutes = MagicLoginToken::getTokenExpiryMinutes($token);

        return response()->json([
            'isValid' => $isValid,
            'expiryMinutes' => $expiryMinutes,
            'message' => $isValid ? 'Token is valid' : 'Token is invalid or expired'
        ]);
    }
}
