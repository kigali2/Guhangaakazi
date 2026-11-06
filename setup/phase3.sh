set -e
PHP=/usr/bin/php8.4
mkdir -p app/Http/Controllers/Admin app/Http/Middleware resources/views/admin/layouts
rm -f config/admin.php
sed -i '/^ADMIN_LOGIN_PATH=/d; /^SESSION_LIFETIME=/d; /^SESSION_EXPIRE_ON_CLOSE=/d' .env
echo "SESSION_LIFETIME=30" >> .env
echo "SESSION_EXPIRE_ON_CLOSE=true" >> .env

cat > app/Providers/AppServiceProvider.php <<'EOF'
<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        if (request()->header('x-forwarded-proto') === 'https') {
            URL::forceScheme('https');
        }
    }
}
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
            return redirect()->guest(route('admin.login'));
        }
        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
        return $response;
    }
}
EOF

cat > app/Http/Controllers/Admin/AuthController.php <<'EOF'
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
        if ($request->user()?->is_admin) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $key = Str::lower($data['email']) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages(['email' => "Too many attempts. Try again in {$seconds} seconds."]);
        }

        $ok = Auth::attempt(
            ['email' => $data['email'], 'password' => $data['password'], 'is_admin' => true],
            $request->boolean('remember')
        );

        if (! $ok) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'login',
            'ip_address' => $request->ip(),
        ]);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login')->with('success', 'You have been signed out.');
    }
}
EOF

cat > app/Http/Controllers/Admin/DashboardController.php <<'EOF'
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\BusinessIdea;
use App\Models\Comment;
use App\Models\ContactMessage;
use App\Models\Opportunity;
use App\Models\Photo;
use App\Models\Skill;
use App\Models\Video;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        $openOpportunities = Opportunity::published()
            ->where(fn ($q) => $q->whereNull('closes_at')->orWhere('closes_at', '>=', $today))
            ->where(fn ($q) => $q->whereNull('opens_at')->orWhere('opens_at', '<=', $today))
            ->count();

        $sum = fn (string $col) => collect([BusinessIdea::class, Skill::class, Opportunity::class, Article::class, Photo::class, Video::class])
            ->sum(fn ($m) => (int) $m::sum($col));

        $stats = [
            ['Business ideas', BusinessIdea::count(), 'primary'],
            ['Published ideas', BusinessIdea::published()->count(), 'success'],
            ['Draft ideas', BusinessIdea::where('status', 'draft')->count(), 'secondary'],
            ['Skills', Skill::count(), 'primary'],
            ['Opportunities', Opportunity::count(), 'primary'],
            ['Open opportunities', $openOpportunities, 'success'],
            ['Articles', Article::count(), 'primary'],
            ['Photos', Photo::count(), 'primary'],
            ['Videos', Video::count(), 'primary'],
            ['Unread messages', ContactMessage::whereNull('read_at')->count(), 'warning'],
            ['Total views', $sum('views_count'), 'info'],
            ['Total likes', $sum('likes_count'), 'danger'],
            ['Total comments', Comment::where('status', 'approved')->count(), 'dark'],
        ];

        $list = fn ($query) => $query->latest()->take(5)->get()->map(fn ($r) => [
            'title' => $r->title,
            'at' => $r->created_at,
            'badge' => $r->status,
        ]);

        $recent = [
            'Recent business ideas' => $list(BusinessIdea::query()),
            'Recent articles' => $list(Article::query()),
            'Recent photos' => $list(Photo::query()),
            'Recent videos' => $list(Video::query()),
            'Recent contact messages' => ContactMessage::latest()->take(5)->get()->map(fn ($m) => [
                'title' => $m->subject . ' (' . $m->name . ')',
                'at' => $m->created_at,
                'badge' => $m->read_at ? 'read' : 'unread',
            ]),
        ];

        return view('admin.dashboard', compact('stats', 'recent'));
    }
}
EOF

cat > resources/scss/app.scss <<'EOF'
$primary: #0b6b3a;
$success: #1a8a4f;
$dark: #0f1f3d;
$warning: #e8a317;
$font-family-sans-serif: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;

@import "bootstrap/scss/bootstrap";

.admin-sidebar {
    background: #0f1f3d;
    --bs-offcanvas-width: 270px;
    --bs-offcanvas-bg: #0f1f3d;
    --bs-offcanvas-color: #fff;
}
@include media-breakpoint-up(lg) {
    .admin-sidebar {
        width: 270px;
        flex-shrink: 0;
        position: sticky;
        top: 0;
        height: 100vh;
        overflow-y: auto;
    }
}
.admin-sidebar .nav-link {
    color: rgba(255, 255, 255, 0.8);
    border-radius: 0.5rem;
    padding: 0.6rem 0.9rem;
}
.admin-sidebar .nav-link:hover { background: rgba(255, 255, 255, 0.08); color: #fff; }
.admin-sidebar .nav-link.active { background: #0b6b3a; color: #fff; }
.admin-sidebar .nav-link.disabled { opacity: 0.45; }
.stat-card { border: 0; border-left: 4px solid var(--bs-primary); box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08); }
EOF

cat > resources/views/admin/layouts/app.blade.php <<'EOF'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Dashboard') | GuhangaAkazi Admin</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-light">
@php
    $nav = [
        ['Dashboard', 'admin.dashboard', '📊'],
        ['Business Ideas', 'admin.business-ideas.index', '💡'],
        ['Skills', 'admin.skills.index', '🎓'],
        ['Opportunities', 'admin.opportunities.index', '🚀'],
        ['Articles', 'admin.articles.index', '📰'],
        ['Photos', 'admin.photos.index', '🖼️'],
        ['Videos', 'admin.videos.index', '🎬'],
        ['Media Library', 'admin.media.index', '🗂️'],
        ['Homepage Slider', 'admin.slides.index', '🎞️'],
        ['Categories', 'admin.categories.index', '🏷️'],
        ['Comments', 'admin.comments.index', '💬'],
        ['Contact Messages', 'admin.messages.index', '✉️'],
        ['SEO Management', 'admin.seo.index', '🔍'],
        ['Website Settings', 'admin.settings.index', '⚙️'],
    ];
@endphp
<div class="d-flex">
    <aside class="offcanvas-lg offcanvas-start admin-sidebar text-white" tabindex="-1" id="sidebar" aria-label="Admin navigation">
        <div class="offcanvas-header d-lg-none">
            <h5 class="offcanvas-title">GuhangaAkazi</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body flex-column p-3">
            <a href="{{ route('admin.dashboard') }}" class="d-none d-lg-block fs-5 fw-bold text-white text-decoration-none mb-3">🌱 GuhangaAkazi</a>
            <ul class="nav nav-pills flex-column gap-1 mb-3">
                @foreach ($nav as [$label, $route, $icon])
                    @php
                        $active = $route === 'admin.dashboard'
                            ? request()->routeIs($route)
                            : request()->routeIs(Str::beforeLast($route, '.') . '.*');
                    @endphp
                    @if (Route::has($route))
                        <li><a class="nav-link {{ $active ? 'active' : '' }}" href="{{ route($route) }}">{{ $icon }} {{ $label }}</a></li>
                    @else
                        <li><span class="nav-link disabled">{{ $icon }} {{ $label }} <span class="badge text-bg-secondary ms-1">Soon</span></span></li>
                    @endif
                @endforeach
            </ul>
            <form method="POST" action="{{ route('admin.logout') }}" class="mt-auto">
                @csrf
                <button class="btn btn-outline-light w-100">Sign Out</button>
            </form>
        </div>
    </aside>

    <div class="flex-grow-1" style="min-width:0">
        <header class="navbar bg-white border-bottom px-3 sticky-top">
            <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Open menu">☰</button>
            <span class="fw-semibold d-none d-lg-inline">@yield('title', 'Dashboard')</span>
            <div class="dropdown ms-auto">
                <button class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">👤 {{ auth()->user()->name }}</button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text small text-muted">{{ auth()->user()->email }}</span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('admin.logout') }}">@csrf
                            <button class="dropdown-item">Sign Out</button>
                        </form>
                    </li>
                </ul>
            </div>
        </header>
        <main class="p-3 p-lg-4">
            @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
EOF

cat > resources/views/admin/login.blade.php <<'EOF'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Login | GuhangaAkazi</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-dark">
<div class="container min-vh-100 d-flex align-items-center justify-content-center py-4">
    <div class="card shadow border-0" style="max-width:420px;width:100%">
        <div class="card-body p-4">
            <h1 class="h4 text-center text-primary fw-bold">🌱 GuhangaAkazi</h1>
            <p class="text-center text-muted mb-4">Administrator sign in</p>
            @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            <form method="POST" action="{{ route('admin.login.submit') }}" novalidate>
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control form-control-lg @error('email') is-invalid @enderror" autocomplete="username" required autofocus>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input id="password" type="password" name="password" class="form-control form-control-lg @error('password') is-invalid @enderror" autocomplete="current-password" required>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label" for="remember">Keep me signed in</label>
                </div>
                <button class="btn btn-primary btn-lg w-100">Sign In</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
EOF

cat > resources/views/admin/dashboard.blade.php <<'EOF'
@extends('admin.layouts.app')
@section('title', 'Dashboard')
@section('content')
<h1 class="h3 mb-3">Welcome back, {{ auth()->user()->name }}</h1>

<div class="row g-3 mb-4">
    @foreach ($stats as [$label, $value, $color])
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card stat-card" style="border-left-color: var(--bs-{{ $color }})">
                <div class="card-body py-3">
                    <div class="text-muted small">{{ $label }}</div>
                    <div class="fs-3 fw-bold">{{ number_format($value) }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3">
    @foreach ($recent as $heading => $rows)
        <div class="col-12 col-lg-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">{{ $heading }}</div>
                <ul class="list-group list-group-flush">
                    @forelse ($rows as $row)
                        <li class="list-group-item d-flex justify-content-between align-items-start gap-2">
                            <div style="min-width:0">
                                <div class="text-truncate">{{ $row['title'] }}</div>
                                <small class="text-muted">{{ $row['at']->diffForHumans() }}</small>
                            </div>
                            @if ($row['badge'])
                                <span class="badge text-bg-{{ in_array($row['badge'], ['published', 'read']) ? 'success' : ($row['badge'] === 'unread' ? 'warning' : 'secondary') }}">{{ ucfirst($row['badge']) }}</span>
                            @endif
                        </li>
                    @empty
                        <li class="list-group-item text-muted">Nothing yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    @endforeach
</div>
@endsection
EOF

python3 - <<'PY'
p = 'routes/web.php'
s = open(p).read()
marker = "// ADMIN ROUTES"
i = s.find(marker)
if i != -1:
    s = s[:i]
for old in ["Route::get('/admin', function () {"]:
    j = s.find(old)
    if j != -1:
        s = s[:j]
s = s.rstrip() + """

// ADMIN ROUTES
Route::get('admin', [\\App\\Http\\Controllers\\Admin\\AuthController::class, 'showLogin'])->name('admin.login');
Route::post('admin', [\\App\\Http\\Controllers\\Admin\\AuthController::class, 'login'])->middleware('throttle:20,1')->name('admin.login.submit');

Route::prefix('admin')->name('admin.')->middleware(\\App\\Http\\Middleware\\EnsureAdmin::class)->group(function () {
    Route::post('logout', [\\App\\Http\\Controllers\\Admin\\AuthController::class, 'logout'])->name('logout');
    Route::get('dashboard', [\\App\\Http\\Controllers\\Admin\\DashboardController::class, 'index'])->name('dashboard');
});
"""
open(p, 'w').write(s)
PY

pkill -f vite || true
rm -f public/hot
npm run build
$PHP artisan optimize:clear
$PHP artisan route:list --path=admin
echo "PHASE 3 COMPLETE"