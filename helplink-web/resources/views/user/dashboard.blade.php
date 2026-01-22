@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="mb-3">Welcome, {{ strtoupper(Auth::user()->name) }}!</h2>
    <p class="text-muted mb-4">Use the navigation bar above to manage your donations and requests.</p>

    <div class="row g-4 mb-4">
        {{-- My Offers --}}
        <div class="col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">🎁 My Offers</h5>
                    <p class="display-6">{{ $totalOffers ?? 0 }}</p>
                    <a href="{{ route('offer.my') }}" class="btn btn-outline-primary btn-sm">View</a>
                </div>
            </div>
        </div>

        {{-- My Requests --}}
        <div class="col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">📩 My Requests</h5>
                    <p class="display-6">{{ $totalRequests ?? 0 }}</p>
                    <a href="{{ route('requests.my') }}" class="btn btn-outline-primary btn-sm">View</a>
                </div>
            </div>
        </div>

        {{-- Claimed Offers --}}
        <div class="col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">✅ Claimed Offers</h5>
                    <p class="display-6">{{ $claimedOffers ?? 0 }}</p>
                    <a href="{{ route('claims.offer.my') }}" class="btn btn-outline-primary btn-sm">View</a>
                </div>
            </div>
        </div>

        {{-- Claimed Requests --}}
        <div class="col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">📦 Claimed Requests</h5>
                    <p class="display-6">{{ $claimedRequests ?? 0 }}</p>
                    <a href="{{ route('claims.request.my') }}" class="btn btn-outline-primary btn-sm">View</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Profile Info --}}
    <div class="mt-4 p-4 border rounded bg-light">
        <h5>👤 Profile Info</h5>
        <p><strong>Email:</strong> {{ Auth::user()->email }}</p>
        <p><strong>Phone:</strong> {{ Auth::user()->phone_number ?? '-' }}</p>
        <p><strong>Address:</strong> {{ Auth::user()->address ?? '-' }}</p>
        <a href="{{ route('user.profile') }}" class="btn btn-sm btn-outline-primary mt-2">Edit Profile</a>
    </div>

    {{-- Logout --}}
    <form method="POST" action="{{ route('logout') }}" class="mt-4">
        @csrf
        <button type="submit" class="btn btn-danger">Logout</button>
    </form>
</div>
@endsection
