@extends('layouts.admin')

@section('title', 'User Details')

@section('content')
<div class="container py-4">

    {{-- ================= PAGE HEADER ================= --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h2 class="fw-bold text-primary mb-1">
                <i class="fas fa-user me-2"></i> User Details
            </h2>
            <small class="text-muted">
                View registered user information
            </small>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.users.edit', $user->id) }}"
               class="btn btn-sm btn-outline-warning rounded-pill px-3">
                <i class="fas fa-pen me-1"></i> Edit
            </a>

            <a href="{{ route('admin.users.index') }}"
               class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    {{-- ================= USER INFO CARD ================= --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body">

            {{-- NAME + STATUS --}}
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <div>
                    <h5 class="fw-bold mb-1">{{ $user->name }}</h5>
                    <div class="text-muted small">{{ $user->email }}</div>
                </div>

                @if($user->email_verified_at)
                    <span class="badge rounded-pill bg-success-subtle text-success px-3 py-2">
                        <i class="fas fa-check-circle me-1"></i> Verified
                    </span>
                @else
                    <span class="badge rounded-pill bg-warning-subtle text-warning px-3 py-2">
                        <i class="fas fa-clock me-1"></i> Unverified
                    </span>
                @endif
            </div>

            <hr class="my-3">

            {{-- DETAILS GRID --}}
            <div class="row g-4 small">

                <div class="col-md-6">
                    <div class="fw-semibold mb-1">Phone Number</div>
                    <div class="text-muted">
                        {{ $user->phone_number ?? '-' }}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="fw-semibold mb-1">Address</div>
                    <div class="text-muted">
                        {{ $user->address ?? '-' }}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="fw-semibold mb-1">Registered At</div>
                    <div class="text-muted">
                        {{ $user->created_at->format('d M Y, h:i A') }}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="fw-semibold mb-1">User ID</div>
                    <div class="text-muted">
                        #{{ $user->id }}
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection
