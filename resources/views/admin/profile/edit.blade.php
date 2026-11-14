@extends('admin.layouts.app')
@section('title', 'Admin Profile')
@section('content')
<h1 class="h4 mb-3">Admin Profile</h1>
@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif
@if ($errors->password->any())
    <div class="alert alert-danger">{{ $errors->password->first() }}</div>
@endif

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <form method="POST" action="{{ route('admin.profile.update') }}" class="card border-0 shadow-sm mb-3"><div class="card-body">
            @csrf
            @method('PUT')
            <h2 class="h6 mb-3">Profile information</h2>
            <div class="d-flex align-items-center gap-3 mb-3">
                @if ($avatar)
                    <img src="{{ $avatar }}" alt="Profile picture" class="rounded-circle" width="72" height="72" style="object-fit:cover">
                @else
                    <div class="rounded-circle bg-success text-white fw-bold d-flex align-items-center justify-content-center" style="width:72px;height:72px;font-size:1.6rem">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</div>
                @endif
                <div class="small text-muted">Upload your picture in Cloudflare, then paste its public link below.</div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="p_avatar">Profile picture link</label>
                <input id="p_avatar" type="text" name="avatar" value="{{ old('avatar', $avatar) }}" class="form-control @error('avatar') is-invalid @enderror" placeholder="https://pub-xxxx.r2.dev/me.jpg">
                @error('avatar')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="p_name">Name</label>
                <input id="p_name" type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="p_email">Email address</label>
                <input id="p_email" type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="p_cur">Current password (only needed to change your email)</label>
                <input id="p_cur" type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" autocomplete="current-password">
                @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-primary">Save profile</button>
        </div></form>

        <form method="POST" action="{{ route('admin.profile.password') }}" class="card border-0 shadow-sm"><div class="card-body">
            @csrf
            @method('PUT')
            <h2 class="h6 mb-3">Change password</h2>
            <div class="mb-3">
                <label class="form-label" for="w_cur">Current password</label>
                <input id="w_cur" type="password" name="current_password" class="form-control" autocomplete="current-password" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="w_new">New password (at least 10 characters, with letters and numbers)</label>
                <input id="w_new" type="password" name="password" class="form-control" autocomplete="new-password" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="w_con">Confirm new password</label>
                <input id="w_con" type="password" name="password_confirmation" class="form-control" autocomplete="new-password" required>
            </div>
            <button class="btn btn-primary">Change password</button>
        </div></form>
    </div>

    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <h2 class="h6 mb-3">Account information</h2>
            <dl class="row mb-0 small">
                <dt class="col-5">Role</dt><dd class="col-7">Administrator</dd>
                <dt class="col-5">Email</dt><dd class="col-7 text-break">{{ $user->email }}</dd>
                <dt class="col-5">Account created</dt><dd class="col-7">{{ $user->created_at?->format('d M Y') }}</dd>
                <dt class="col-5">Total sign-ins</dt><dd class="col-7">{{ number_format($loginCount) }}</dd>
                <dt class="col-5">Previous sign-in</dt><dd class="col-7">{{ $lastLogin ? $lastLogin->format('d M Y, H:i') : 'None yet' }}</dd>
            </dl>
        </div></div>
    </div>
</div>
@endsection
