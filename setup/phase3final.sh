set -e
PHP=/usr/bin/php8.4

rm -f config/admin.php
sed -i '/^ADMIN_LOGIN_PATH=/d' .env

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
            return redirect()->guest(route('admin.login'));
        }
        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
        return $response;
    }
}
EOF

python3 - <<'PY'
p = 'routes/web.php'
s = open(p).read()
i = s.find("Route::get('/admin', function () {")
if i != -1:
    s = s[:i]
s = s.rstrip() + """

Route::get('admin', [\\App\\Http\\Controllers\\Admin\\AuthController::class, 'showLogin'])->name('admin.login');
Route::post('admin', [\\App\\Http\\Controllers\\Admin\\AuthController::class, 'login'])->middleware('throttle:20,1')->name('admin.login.submit');

Route::prefix('admin')->name('admin.')->middleware(\\App\\Http\\Middleware\\EnsureAdmin::class)->group(function () {
    Route::post('logout', [\\App\\Http\\Controllers\\Admin\\AuthController::class, 'logout'])->name('logout');
    Route::get('dashboard', [\\App\\Http\\Controllers\\Admin\\DashboardController::class, 'index'])->name('dashboard');
});
"""
open(p, 'w').write(s)
PY

sed -i '/^SESSION_LIFETIME=/d; /^SESSION_EXPIRE_ON_CLOSE=/d' .env
echo "SESSION_LIFETIME=30" >> .env
echo "SESSION_EXPIRE_ON_CLOSE=true" >> .env

$PHP artisan optimize:clear
$PHP artisan route:list --path=admin
echo "PHASE 3 FINAL COMPLETE"