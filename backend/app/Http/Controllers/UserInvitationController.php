<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserInvitation;
use App\Notifications\UserInvitationNotification;
use App\Services\Audit\AuditLogger;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserInvitationController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function store(Request $request): JsonResponse
    {
        $actor = $request->user(); abort_unless($actor->can('users.update') && $actor->can('roles.manage'), 403);
        $allowedRoles = array_values(config('access_control.roles', []));
        $data = $request->validate(['name'=>['required','string','max:255'],'email'=>['required','email','max:255',Rule::unique('users','email')],'roles'=>['required','array','min:1'],'roles.*'=>['required','string','distinct',Rule::in($allowedRoles)]]);
        if (in_array('Owner', $data['roles'], true) && !$actor->hasRole('Owner')) abort(403);
        $email=Str::lower(trim($data['email']));$token=Str::random(64);
        $invitation=DB::transaction(function()use($data,$email,$token,$actor):UserInvitation{$existing=UserInvitation::query()->whereRaw('LOWER(email) = ?',[$email])->lockForUpdate()->first();if($existing&&$existing->accepted_at)throw ValidationException::withMessages(['email'=>'This invitation has already been accepted.']);return UserInvitation::query()->updateOrCreate(['email'=>$email],['name'=>trim($data['name']),'token_hash'=>hash('sha256',$token),'roles'=>array_values($data['roles']),'invited_by'=>$actor->id,'expires_at'=>now()->addDays(7),'accepted_at'=>null,'revoked_at'=>null]);});
        Notification::route('mail',$invitation->email)->notify(new UserInvitationNotification($invitation,$token));
        $this->audit->record($actor,'invitation.sent','invitation',$invitation->id,$invitation->name,['added_roles'=>$invitation->roles,'status'=>'pending']);
        return response()->json(['data'=>$this->payload($invitation),'message'=>'Invitation sent.'],201);
    }

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('users.view'),403);$items=UserInvitation::query()->latest()->limit(100)->get()->map(fn(UserInvitation $i)=>$this->payload($i));return response()->json(['data'=>$items]);
    }

    public function resend(Request $request, UserInvitation $invitation): JsonResponse
    {
        $actor=$request->user();abort_unless($actor->can('users.update')&&$actor->can('roles.manage'),403);if($invitation->accepted_at)throw ValidationException::withMessages(['invitation'=>'Accepted invitations cannot be resent.']);if(in_array('Owner',$invitation->roles,true)&&!$actor->hasRole('Owner'))abort(403);
        $token=Str::random(64);$invitation->forceFill(['token_hash'=>hash('sha256',$token),'expires_at'=>now()->addDays(7),'revoked_at'=>null])->save();Notification::route('mail',$invitation->email)->notify(new UserInvitationNotification($invitation,$token));
        $this->audit->record($actor,'invitation.resent','invitation',$invitation->id,$invitation->name,['status'=>'pending']);
        return response()->json(['data'=>$this->payload($invitation->fresh()),'message'=>'Invitation resent.']);
    }

    public function revoke(Request $request, UserInvitation $invitation): JsonResponse
    {
        $actor=$request->user();abort_unless($actor->can('users.update')&&$actor->can('roles.manage'),403);if(in_array('Owner',$invitation->roles,true)&&!$actor->hasRole('Owner'))abort(403);if($invitation->accepted_at)throw ValidationException::withMessages(['invitation'=>'Accepted invitations cannot be revoked.']);$invitation->forceFill(['revoked_at'=>now()])->save();
        $this->audit->record($actor,'invitation.revoked','invitation',$invitation->id,$invitation->name,['status'=>'revoked']);
        return response()->json(['data'=>$this->payload($invitation->fresh()),'message'=>'Invitation revoked.']);
    }

    public function show(Request $request): JsonResponse
    {
        $data=$request->validate(['email'=>['required','email'],'token'=>['required','string']]);$invitation=$this->resolveInvitation($data['email'],$data['token']);return response()->json(['data'=>['email'=>$invitation->email,'name'=>$invitation->name,'roles'=>$invitation->roles,'expires_at'=>$invitation->expires_at]]);
    }

    public function accept(Request $request): JsonResponse
    {
        $data=$request->validate(['email'=>['required','email'],'token'=>['required','string'],'password'=>['required','confirmed',Password::min(10)->letters()->mixedCase()->numbers()]]);$invitation=$this->resolveInvitation($data['email'],$data['token']);
        $user=DB::transaction(function()use($invitation,$data):User{$locked=UserInvitation::query()->lockForUpdate()->findOrFail($invitation->id);if(!$locked->isPending())throw ValidationException::withMessages(['invitation'=>'This invitation is no longer valid.']);if(User::query()->whereRaw('LOWER(email) = ?',[Str::lower($locked->email)])->exists())throw ValidationException::withMessages(['email'=>'An account already exists for this email.']);$user=User::query()->create(['name'=>$locked->name,'email'=>$locked->email,'password'=>Hash::make($data['password'])]);$user->syncRoles($locked->roles);$locked->forceFill(['accepted_at'=>now()])->save();event(new Registered($user));return $user;});
        $this->audit->record(null,'invitation.accepted','user',$user->id,$user->name,['status'=>'accepted'],$user);
        return response()->json(['message'=>'Invitation accepted. You can now sign in.']);
    }

    private function resolveInvitation(string $email,string $token):UserInvitation{$invitation=UserInvitation::query()->whereRaw('LOWER(email) = ?',[Str::lower(trim($email))])->first();if(!$invitation||!hash_equals($invitation->token_hash,hash('sha256',$token))||!$invitation->isPending())throw ValidationException::withMessages(['invitation'=>'This invitation is invalid, expired, or revoked.']);return $invitation;}
    private function payload(UserInvitation $invitation):array{$status=$invitation->accepted_at?'accepted':($invitation->revoked_at?'revoked':($invitation->expires_at->isPast()?'expired':'pending'));return ['id'=>$invitation->id,'name'=>$invitation->name,'email'=>$invitation->email,'roles'=>$invitation->roles,'status'=>$status,'expires_at'=>$invitation->expires_at,'created_at'=>$invitation->created_at];}
}
