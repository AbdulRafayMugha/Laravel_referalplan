<?php

namespace App\Http\Controllers;

use App\Models\BankDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmailMail;
use App\Mail\PasswordResetMail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'nullable|in:admin,affiliate,client,coordinator',
            'referrer_code' => 'nullable|string',
            'coordinator_id' => 'nullable|uuid',
        ]);

        // generate unique referral code
        do {
            $referral = strtoupper(Str::random(8));
        } while (User::where('referral_code', $referral)->exists());

        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = Hash::make($data['password']);
        $user->role = $data['role'] ?? 'affiliate';
        $user->referral_code = $referral;
        $user->is_active = true;

        // resolve referrer and coordinator similar to Node logic (simplified)
        if (!empty($data['referrer_code'])) {
            $ref = User::where('referral_code', $data['referrer_code'])->where('is_active', true)->first();
            if ($ref) {
                $user->referrer_id = $ref->id;
                if ($ref->role === 'coordinator') {
                    $user->coordinator_id = $ref->id;
                } elseif ($ref->role === 'affiliate' && $ref->coordinator_id) {
                    $user->coordinator_id = $ref->coordinator_id;
                }
            }
        } elseif (!empty($data['coordinator_id'])) {
            $user->coordinator_id = $data['coordinator_id'];
        } else {
            // No referrer/coordinator provided: auto-assign super coordinator if exists
            $super = User::where('role', 'coordinator')->where('is_super_coordinator', true)->first();
            if ($super) {
                $user->coordinator_id = $super->id;
            }
        }

        // email verification token
        $user->email_verification_token = Str::uuid()->toString();
        $user->email_verification_expires = now()->addDay();
        $user->save();

        // default affiliate link for affiliates
        if ($user->role === 'affiliate') {
            \App\Models\AffiliateLink::create([
                'affiliate_id' => $user->id,
                'link_code' => $user->referral_code,
            ]);

            \App\Models\Bonus::create([
                'affiliate_id' => $user->id,
                'type' => 'signup',
                'description' => 'Welcome bonus for joining as affiliate',
                'amount' => 10.00,
            ]);
        }

        // send verification email
        $verifyUrl = rtrim(env('FRONTEND_URL', config('app.url')), '/') . '/verify-email?token=' . $user->email_verification_token;
        Mail::to($user->email)->send(new VerifyEmailMail($verifyUrl));

        $token = $user->createToken('api')->plainTextToken;
        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('api')->plainTextToken;
        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function profile(Request $request)
    {
        return response()->json($request->user());
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
        ]);
        $user->fill($data)->save();
        return response()->json($user);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);
        $user = $request->user();
        $user->password = Hash::make($request->input('password'));
        $user->save();
        return response()->json(['message' => 'Password updated']);
    }

    public function verifyEmail(Request $request)
    {
        $request->validate(['token' => 'required|string']);
        $user = User::where('email_verification_token', $request->input('token'))
            ->where(function($q){ $q->whereNull('email_verification_expires')->orWhere('email_verification_expires', '>=', now()); })
            ->first();
        if (!$user) {
            return response()->json(['message' => 'Invalid or expired token'], 422);
        }
        $user->email_verified = true;
        $user->email_verified_at = now();
        $user->email_verification_token = null;
        $user->email_verification_expires = null;
        $user->save();
        return response()->json(['message' => 'Email verified']);
    }

    public function resendVerification(Request $request)
    {
        $user = $request->user();
        if ($user->email_verified) {
            return response()->json(['message' => 'Already verified']);
        }
        $user->email_verification_token = Str::uuid()->toString();
        $user->email_verification_expires = now()->addDay();
        $user->save();
        $verifyUrl = rtrim(env('FRONTEND_URL', config('app.url')), '/') . '/verify-email?token=' . $user->email_verification_token;
        Mail::to($user->email)->send(new VerifyEmailMail($verifyUrl));
        return response()->json(['message' => 'Verification email resent']);
    }

    public function requestPasswordReset(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->input('email'))->first();
        if ($user) {
            $user->password_reset_token = Str::uuid()->toString();
            $user->password_reset_expires = now()->addHour();
            $user->save();
            $resetUrl = rtrim(env('FRONTEND_URL', config('app.url')), '/') . '/reset-password?token=' . $user->password_reset_token;
            Mail::to($user->email)->send(new PasswordResetMail($resetUrl));
        }
        return response()->json(['message' => 'If the email exists, a reset link has been sent']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|confirmed|min:6',
        ]);
        $user = User::where('password_reset_token', $request->input('token'))
            ->where(function($q){ $q->whereNull('password_reset_expires')->orWhere('password_reset_expires', '>=', now()); })
            ->first();
        if (!$user) {
            return response()->json(['message' => 'Invalid or expired token'], 422);
        }
        $user->password = \Hash::make($request->input('password'));
        $user->password_reset_token = null;
        $user->password_reset_expires = null;
        $user->save();
        return response()->json(['message' => 'Password has been reset']);
    }
}


