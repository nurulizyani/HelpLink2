@extends('layouts.app')

@section('content')
<style>
    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.1);
        background-color: #ffffff;
    }

    h3 span {
        color: #2c3e50;
    }

    .btn-theme {
        background-color: #2c3e50;
        color: #fff;
        border: none;
        transition: background-color 0.3s ease;
    }

    .btn-theme:hover {
        background-color: #506a75;
    }

    .form-control:focus {
        border-color: #b4c6ca;
        box-shadow: 0 0 0 0.2rem rgba(180, 198, 202, 0.25);
    }

    a {
        color: #2c3e50;
    }

    a:hover {
        color: #1a252f;
        text-decoration: none;
    }

    /* Responsive image */
    @media (max-width: 768px) {
        .img-fluid {
            max-height: 250px;
        }
    }
</style>

<div class="container py-5">
    <div class="row justify-content-center align-items-center">
        <div class="col-md-6">
            <div class="card p-4">
                <h3 class="text-center mb-4">
                    <strong><span>HelpLink</span></strong> Login
                </h3>

                <p class="text-muted text-center mb-4" style="font-size: 0.95rem;">
                    Connecting hearts, one donation at a time.
                </p>

                @if(request()->has('registered'))
                    <div class="alert alert-success text-center">
                        Registration successful. Please login.
                    </div>
                @endif


                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-3">
                        <input type="email" name="email" class="form-control" placeholder="Email" required autofocus>
                    </div>

                    <div class="mb-3">
                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                    </div>

                    <div class="mb-3 form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label" for="remember">Remember Me</label>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-theme">Login</button>
                    </div>

                    <div class="text-center">
                        <p>Don't have an account? <a href="{{ route('register') }}">Register here</a></p>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}">Forgot Your Password?</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        {{-- Gambar login --}}
        <div class="col-md-5 d-none d-md-block text-center">
            <img src="{{ asset('images/login.jpg') }}" class="img-fluid" alt="Login Image" style="max-height: 400px;">
        </div>
    </div>
</div>
@endsection
