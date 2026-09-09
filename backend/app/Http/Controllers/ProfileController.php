<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->payload($request)]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'username' => ['required', 'string', 'min:3', 'max:60', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'username')->ignore($user->id)],
            'job_title' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:1000'],
        ]);
        $data['name'] = trim($data['first_name'].' '.$data['last_name']);
        $user->update($data);
        return response()->json(['user' => $this->payload($request)]);
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate(['avatar' => ['required', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max('5mb')]]);
        $user = $request->user();
        $file = $request->file('avatar');
        $extension = strtolower($file->guessExtension() ?: $file->extension());
        $path = 'avatars/'.$user->id.'/'.Str::uuid().'.'.$extension;
        $disk = Storage::disk('local');
        $disk->putFileAs('avatars/'.$user->id, $file, basename($path));
        $oldDisk = $user->avatar_disk; $oldPath = $user->avatar_path;
        $user->update(['avatar_disk' => 'local', 'avatar_path' => $path]);
        if ($oldDisk && $oldPath) Storage::disk($oldDisk)->delete($oldPath);
        return response()->json(['user' => $this->payload($request)]);
    }

    public function avatar(Request $request): BinaryFileResponse|StreamedResponse
    {
        $user = $request->user();
        abort_unless($user->avatar_disk && $user->avatar_path, 404);
        $disk = Storage::disk($user->avatar_disk);
        abort_unless($disk->exists($user->avatar_path), 404);
        return $disk->response($user->avatar_path, 'avatar', ['X-Content-Type-Options' => 'nosniff'], 'inline');
    }

    public function destroyAvatar(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->avatar_disk && $user->avatar_path) Storage::disk($user->avatar_disk)->delete($user->avatar_path);
        $user->update(['avatar_disk' => null, 'avatar_path' => null]);
        return response()->json(['user' => $this->payload($request)]);
    }

    private function payload(Request $request): array
    {
        $user = $request->user()->fresh();
        return [
            'id' => $user->id, 'name' => $user->name, 'first_name' => $user->first_name, 'last_name' => $user->last_name,
            'username' => $user->username, 'job_title' => $user->job_title, 'bio' => $user->bio, 'email' => $user->email,
            'has_avatar' => (bool) ($user->avatar_disk && $user->avatar_path),
        ];
    }
}
