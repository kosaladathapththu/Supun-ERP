<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Sign in · Camy Smart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body{min-height:100vh;background:#f3f6fb;font-family:Inter,Segoe UI,sans-serif}
        .login-panel{width:min(440px,92vw);border:0;border-radius:20px;box-shadow:0 20px 60px rgba(15,31,56,.13)}
        .logo{width:150px;height:112px;object-fit:contain;object-position:left center;display:block}
        .form-control{padding:.78rem 1rem;border-radius:10px}.btn{padding:.75rem;border-radius:10px}
        .password-toggle{border-radius:0 10px 10px 0;padding:.75rem 1rem}.password-input{border-radius:10px 0 0 10px!important}
    </style>
</head>
<body class="d-grid align-items-center">
<div class="card login-panel mx-auto"><div class="card-body p-4 p-md-5">
    <img class="logo mb-4" src="{{ asset('images/camy-smart-logo.png') }}" alt="Camy Smart logo">
    <h2 class="fw-bold mb-1">Welcome back</h2><p class="text-muted mb-1">Sign in to Camy Smart</p><p class="text-muted small mb-4">A Company of Supun Group of Companies</p>
    @if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('login.store') }}">@csrf
        <div class="mb-3"><label class="form-label" for="email">Email address</label><input class="form-control" id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"></div>
        <div class="mb-3"><label class="form-label" for="password">Password</label><div class="input-group"><input class="form-control password-input" id="password" type="password" name="password" required autocomplete="current-password"><button class="btn btn-outline-secondary password-toggle" id="toggle-password" type="button" aria-label="Show password" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button></div></div>
        <div class="form-check mb-4"><input class="form-check-input" type="checkbox" name="remember" value="1" id="remember"><label class="form-check-label" for="remember">Remember me</label></div>
        <button class="btn btn-primary w-100 fw-semibold">Sign in <i class="bi bi-arrow-right ms-1"></i></button>
    </form><p class="text-center text-muted small mb-0 mt-4">Authorized users only</p>
</div></div>
<script>
const password=document.getElementById('password'),toggle=document.getElementById('toggle-password'),icon=toggle.querySelector('i');
toggle.addEventListener('click',()=>{const visible=password.type==='text';password.type=visible?'password':'text';icon.className=visible?'bi bi-eye':'bi bi-eye-slash';toggle.setAttribute('aria-label',visible?'Show password':'Hide password');toggle.setAttribute('aria-pressed',String(!visible));password.focus();});
</script>
</body></html>
