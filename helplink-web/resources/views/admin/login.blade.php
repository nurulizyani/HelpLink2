@extends('layouts.guest')

@section('title', 'Admin Login')

@section('content')
<style>
    body {
        background: linear-gradient(135deg, #e0ecff, #f8fbff);
    }
    .login-card {
        border: none;
        box-shadow: 0 15px 35px rgba(0,0,0,0.08);
    }
    .login-logo {
        width: 60px;
        height: 60px;
        background: #2563EB;
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        margin: 0 auto 15px;
        font-weight: bold;
    }
    .login-subtitle {
        font-size: 14px;
        color: #64748B;
    }
</style>

<div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="card login-card p-4 rounded-4" style="width: 100%; max-width: 420px;">

        <div class="text-center mb-4">
            <div class="login-logo">H</div>
            <h4 class="fw-bold text-primary mb-1">HelpLink Admin</h4>
            <div class="login-subtitle">Administrative Access Only</div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger rounded-3 py-2">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login') }}" onsubmit="handleLogin()">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-semibold">Email Address</label>
                <input
                    type="email"
                    name="email"
                    class="form-control rounded-3"
                    placeholder="Enter your email"
                    required
                    autofocus
                >
            </div>

            <div class="mb-2">
                <label class="form-label fw-semibold">Password</label>
                <div class="input-group">
                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="form-control rounded-start-3"
                        placeholder="Enter your password"
                        required
                    >
                    <span class="input-group-text bg-white" style="cursor:pointer" onclick="togglePassword()">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </span>
                </div>
            </div>

            <div class="text-muted mb-4" style="font-size: 13px;">
                Unauthorized access is strictly prohibited.
            </div>

            <button type="submit" id="loginBtn" class="btn btn-primary w-100 rounded-3 fw-semibold py-2">
                Login
            </button>
        </form>

        <div class="text-center mt-4 text-muted" style="font-size: 13px;">
            © {{ date('Y') }} HelpLink Admin Panel
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const pass = document.getElementById('password');
    const icon = document.getElementById('toggleIcon');

    if (pass.type === 'password') {
        pass.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        pass.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

function handleLogin() {
    const btn = document.getElementById('loginBtn');
    btn.disabled = true;
    btn.innerHTML = 'Signing in...';
}
</script>
@endsection
