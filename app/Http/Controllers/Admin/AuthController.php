<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if ($request->user()?->is_admin) { return redirect()->route("admin.dashboard"); }
        return view("admin.login");
    }
    public function login(Request $request)
    {
        $data = $request->validate(["email" => "required|email", "password" => "required|string"]);
        $key = Str::lower($data["email"]) . "|" . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(["email" => "Too many attempts. Try again in " . RateLimiter::availableIn($key) . " seconds."]);
        }
        $ok = Auth::attempt(["email" => $data["email"], "password" => $data["password"], "is_admin" => true], $request->boolean("remember"));
        if (! $ok) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(["email" => "These credentials do not match our records."]);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        ActivityLog::create(["user_id" => Auth::id(), "action" => "login", "ip_address" => $request->ip()]);
        return redirect()->intended(route("admin.dashboard"));
    }
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route("admin.login")->with("success", "You have been signed out.");
    }
}
