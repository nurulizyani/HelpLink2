@extends('layouts.app')

@section('content')
<style>
    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.1);
        background-color: #ffffff;
        padding: 2rem;
    }

    h3 span {
        color: #2c3e50;
    }

    .btn-theme {
        background-color: #2c3e50;
        color: #fff;
        border: none;
        height: 45px;
        font-weight: bold;
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

    .register-container {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: center;
        gap: 2rem;
    }

    .row.align-items-center {
        min-height: 80vh;
    }

    @media (max-width: 992px) {
    .register-container {
        flex-direction: column-reverse;
        gap: 1.5rem;
    }

    .register-img,
    .img-fluid {
        max-height: 250px;
    }

    .form-control {
        height: 45px;
        font-size: 0.95rem;
    }

    .card {
        padding: 1.5rem;
    }

}
</style>

<div class="container py-5">
    <div class="register-container">
        <!-- Form Section -->
        <div class="col-lg-6 col-md-8">
            <div class="card">
                <h3 class="text-center mb-2"><strong><span>HelpLink</span></strong> Registration</h3>
                <p class="text-muted text-center mb-4" style="font-size: 0.95rem;">
                    Join us to help build a more caring community.
                </p>

                <form method="POST" action="{{ route('register') }}">
                    @csrf

                    <div class="mb-3">
                        <input type="text" name="name" class="form-control" placeholder="Full Name" required value="{{ old('name') }}">
                    </div>

                    <div class="mb-3">
                        <input type="email" name="email" class="form-control" placeholder="Email" required value="{{ old('email') }}">
                    </div>

                    <div class="mb-3">
                        <input type="text" name="phone_number" class="form-control" placeholder="Phone Number" required value="{{ old('phone_number') }}">
                    </div>

                    <div class="mb-3">
                        <input type="text" name="address" class="form-control" placeholder="Address" required value="{{ old('address') }}">
                    </div>

                    <div class="mb-3">
                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                    </div>

                    <div class="mb-3">
                        <input type="password" name="password_confirmation" class="form-control" placeholder="Confirm Password" required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-theme">Register</button>
                    </div>

                    <p class="text-center mt-3">
                        Already have an account? <a href="{{ route('login') }}">Login here</a>
                    </p>
                </form>
            </div>
        </div>

        <!-- Image Section -->
        <div class="col-lg-5 col-md-6 text-center">
            <img src="{{ asset('images/register.jpg') }}" class="img-fluid rounded" style="max-height: 350px;">

        </div>
    </div>
</div>
@endsection
