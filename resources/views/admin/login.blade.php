<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow"><title>Admin Login | GuhangaAkazi</title>
@vite(["resources/scss/app.scss", "resources/js/app.js"])</head><body class="bg-dark">
<div class="container min-vh-100 d-flex align-items-center justify-content-center py-4"><div class="card shadow border-0" style="max-width:420px;width:100%"><div class="card-body p-4">
<h1 class="h4 text-center text-primary fw-bold">GuhangaAkazi</h1><p class="text-center text-muted mb-4">Administrator sign in</p>
@if (session("success"))<div class="alert alert-success">{{ session("success") }}</div>@endif
<form method="POST" action="{{ route("admin.login.submit") }}" novalidate>@csrf
<div class="mb-3"><label for="email" class="form-label">Email address</label><input id="email" type="email" name="email" value="{{ old("email") }}" class="form-control form-control-lg @error("email") is-invalid @enderror" autocomplete="username" required autofocus>@error("email")<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="mb-3"><label for="password" class="form-label">Password</label><input id="password" type="password" name="password" class="form-control form-control-lg" autocomplete="current-password" required></div>
<div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="remember" id="remember"><label class="form-check-label" for="remember">Keep me signed in</label></div>
<button class="btn btn-primary btn-lg w-100">Sign In</button></form></div></div></div></body></html>
