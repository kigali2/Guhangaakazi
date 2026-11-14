<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Support\R2;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();
        $logins = ActivityLog::where('user_id', $user->id)->where('action', 'login');

        return view('admin.profile.edit', [
            'user' => $user,
            'avatar' => $user->profile_image_key ? R2::url($user->profile_image_key) : '',
            'loginCount' => (clone $logins)->count(),
            'lastLogin' => (clone $logins)->latest()->skip(1)->first()?->created_at,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'required|string|min:2|max:100',
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'avatar' => 'nullable|string|max:600',
            'current_password' => 'nullable|string',
        ]);

        $newEmail = strtolower($data['email']);
        if ($newEmail !== strtolower($user->email)) {
            if (empty($data['current_password']) || ! Hash::check($data['current_password'], $user->password)) {
                throw ValidationException::withMessages(['current_password' => 'Enter your current password to change your email address.']);
            }
        }

        $avatar = null;
        if (! empty($data['avatar'])) {
            $avatar = R2::keyFromUrl($data['avatar']);
            if (! $avatar) {
                throw ValidationException::withMessages(['avatar' => 'Paste a link from your R2 public URL (' . config('r2.url') . ').']);
            }
        }

        $user->forceFill([
            'name' => strip_tags($data['name']),
            'email' => $newEmail,
            'profile_image_key' => $avatar,
        ])->save();

        ActivityLog::create(['user_id' => $user->id, 'action' => 'profile_updated', 'ip_address' => $request->ip()]);

        return back()->with('success', 'Profile updated.');
    }

    public function password(Request $request)
    {
        $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ]);

        $user = $request->user();
        $user->forceFill(['password' => $request->input('password')])->save();
        $request->session()->regenerate();

        ActivityLog::create(['user_id' => $user->id, 'action' => 'password_changed', 'ip_address' => $request->ip()]);

        return back()->with('success', 'Password changed.');
    }
}
