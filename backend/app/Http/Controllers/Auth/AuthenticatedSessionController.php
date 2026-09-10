<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\LoginChallenge;
use App\Models\User;
use App\Notifications\LoginVerificationCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function store(LoginRequest $request): JsonResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);
        $user = User::where('email', $credentials['email'])->first();
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => ['The email or password is incorrect.']]);
        }
        LoginChallenge::where('user_id', $user->id)->whereNull('consumed_at')->delete();
        $code = (string) random_int(100000, 999999);
        $challenge = LoginChallenge::create(['id'=>(string)Str::uuid(),'user_id'=>$user->id,'code_hash'=>Hash::make($code),'remember'=>$request->boolean('remember'),'expires_at'=>now()->addMinutes(10)]);
        try { $user->notify(new LoginVerificationCode($code)); }
        catch (\Throwable $e) { $challenge->delete(); report($e); return response()->json(['message'=>'We could not send the verification email. Please retry.'],503); }
        return response()->json(['requires_2fa'=>true,'challenge_id'=>$challenge->id,'message'=>'Verification code sent to your email.']);
    }

    public function verify(Request $request): JsonResponse
    {
        $data=$request->validate(['challenge_id'=>['required','uuid'],'code'=>['required','digits:6']]);
        $challenge=LoginChallenge::whereKey($data['challenge_id'])->whereNull('consumed_at')->first();
        if(!$challenge || $challenge->expires_at->isPast()) return response()->json(['message'=>'This verification code has expired. Please sign in again.'],422);
        if($challenge->attempts>=5) return response()->json(['message'=>'Too many verification attempts. Please sign in again.'],429);
        if(!Hash::check($data['code'],$challenge->code_hash)) { $challenge->increment('attempts'); return response()->json(['message'=>'The verification code is incorrect.'],422); }
        $challenge->forceFill(['consumed_at'=>now()])->save();
        Auth::loginUsingId($challenge->user_id,$challenge->remember); $request->session()->regenerate();
        $user=$request->user();
        return response()->json(['message'=>'Signed in successfully.','user'=>$user->identityPayload()]);
    }

    public function destroy(Request $request): JsonResponse
    {
        Auth::guard('web')->logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return response()->json(['message'=>'Signed out successfully.']);
    }
}
