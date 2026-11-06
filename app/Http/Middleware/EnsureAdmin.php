<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class EnsureAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user || ! $user->is_admin) {
            return redirect()->guest(route("admin.login"));
        }
        $response = $next($request);
        $response->headers->set("X-Robots-Tag", "noindex, nofollow");
        $response->headers->set("Cache-Control", "no-store, no-cache, must-revalidate, private");
        return $response;
    }
}
