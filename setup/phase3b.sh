set -e
PHP=/usr/bin/php8.4

cat > config/admin.php <<'EOF'
<?php

return [
    'login_path' => trim(env('ADMIN_LOGIN_PATH', 'admin/login'), '/'),
];
EOF

cat > app/Http/Middleware/EnsureAdmin.php <<'EOF'
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
            abort(404);
        }
        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $response->headers->set('Cache-Control', 'no-store, private');
        return $response;
    }
}
EOF

python3 - <<'PY'
p = 'routes/web.php'
s = open(p).read()
marker = "Route::get('/admin', function () {"
i = s.index(marker)
s = s[:i] + """Route::get('/admin', function () {
    abort_unless(auth()->user()?->is_admin, 404);
    return redirect()->route('admin.dashboard');
});

Route::get(config('admin.login_path'), [\\App\\Http\\Controllers\\Admin\\AuthController::class, 'showLogin'])->name('admin.login');
Route::post(config('admin.login_path'), [\\App\\Http\\Controllers\\Admin\\AuthController::class, 'login'])->middleware('throttle:20,1')->name('admin.login.submit');

Route::prefix('admin')->name('admin.')->middleware(\\App\\Http\\Middleware\\EnsureAdmin::class)->group(function () {
    Route::post('logout', [\\App\\Http\\Controllers\\Admin\\AuthController::class, 'logout'])->name('logout');
    Route::get('dashboard', [\\App\\Http\\Controllers\\Admin\\DashboardController::class, 'index'])->name('dashboard');
});
"""
open(p, 'w').write(s)
PY

sed -i '/^ADMIN_LOGIN_PATH=/d' .env
echo "ADMIN_LOGIN_PATH=team-$(openssl rand -hex 6)" >> .env

$PHP artisan optimize:clear
$PHP artisan route:list --path=admin
echo "-----"
echo "YOUR SECRET LOGIN ADDRESS:"
grep ADMIN_LOGIN_PATH .env
echo "PHASE 3B COMPLETE"