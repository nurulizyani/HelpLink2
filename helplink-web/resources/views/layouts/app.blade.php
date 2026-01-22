<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
    @yield('title', ucwords(str_replace('.', ' ', Route::currentRouteName())) . ' | HelpLink')
</title>


    <!-- Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom Navbar Styling -->
    <style>
        .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: 0.3s ease;
}

        .sticky-top {
            z-index: 1030;
            backdrop-filter: blur(5px);
            background-color: rgba(255, 255, 255, 0.95);
        }

        body {
        font-family: 'Nunito', sans-serif;
        font-weight: 400;
        color: #333;
    }

    h1, h2, h3, h4, h5, h6 {
        font-weight: 700;
    }

    label {
        font-weight: 600;
    }

    .form-control {
        font-weight: 400;
    }

    .btn {
        font-weight: 600;
    }

    .navbar-brand {
        font-weight: 700;
        letter-spacing: -0.5px;
    }
    </style>
</head>
<body class="bg-light"> {{-- Soft background color --}}
@if(Auth::user() && !Auth::user()->hasVerifiedEmail())
    <div class="alert alert-warning text-center mb-0">
        Please verify your email. <a href="{{ route('verification.notice') }}">Click here</a>
    </div>
@endif

<div id="app">
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-md navbar-light bg-light shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="{{ url('/') }}">
                HelpLink
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarContent" aria-controls="navbarContent"
                    aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">
                <!-- Left Side -->
                <ul class="navbar-nav me-auto">
                    @auth
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('dashboard') }}">🏠 Dashboard</a>
                        </li>
                    @endauth
                </ul>

                <!-- Right Side -->
                <ul class="navbar-nav ms-auto align-items-center">
                    @guest
                        @if (Route::has('login'))
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('login') }}">Login</a>
                            </li>
                        @endif
                        @if (Route::has('register'))
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('register') }}">Register</a>
                            </li>
                        @endif
                    @else
                        <!-- Donation Dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="donationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                🧺 Donation
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="donationDropdown">
                                <li><a class="dropdown-item" href="{{ route('claims.offer.available') }}">🔍 View All Offers</a></li>
                                <li><a class="dropdown-item" href="{{ route('offer.create') }}">➕ Create Offer</a></li>
                                <li><a class="dropdown-item" href="{{ route('offer.my') }}">📦 My Offers</a></li>
                            </ul>
                        </li>

                        <!-- Request Dropdown -->
                        <li class="nav-item dropdown">
                            <a id="requestDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                🧾 Request
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('claims.request.available') }}">🔍 View Public Requests</a>
                                <li><a class="dropdown-item" href="{{ route('requests.create') }}">➕ Create Request</a></li>
                                <li><a class="dropdown-item" href="{{ route('requests.my') }}">📦 My Requests</a></li>
                            </ul>
                        </li>

                        <!-- Claims -->
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('claims.offer.my') }}">🎁 Claimed Offers</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('claims.request.my') }}">📥 Claimed Requests</a>
                        </li>


                       <li class="nav-item">
                            <a class="nav-link position-relative" href="{{ route('chat.inbox') }}">
                                💬 Messages
                                @if($unreadMessages > 0)
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        {{ $unreadMessages }}
                                    </span>
                                @endif
                            </a>
                        </li>

 

                        <!-- Profile Dropdown -->
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        👤 {{ Auth::user()->name }}
    </a>
    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
        <li><a class="dropdown-item" href="{{ route('user.profile') }}">👤 Profile</a></li>

        @if(!Auth::user()->telegram_chat_id)
    <li class="nav-item">
        <a class="nav-link text-primary fw-semibold"
           href="https://t.me/+j_gp2_Q-hn4xMjI1"
           target="_blank">
           📢 Join Our Telegram
        </a>
    </li>
@endif


        <li><hr class="dropdown-divider"></li>

        <li>
            <a class="dropdown-item text-danger" href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                🔓 Logout
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>
        </li>
    </ul>
</li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="py-4">
        @yield('content')
    </main>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

{{-- Page Specific Scripts --}}
@yield('scripts')
</body>
</html>
