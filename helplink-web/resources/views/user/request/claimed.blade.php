@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h3 class="mb-4 fw-bold text-primary">📥 Your Claimed Requests</h3>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-4">
        @forelse ($claims as $claim)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm border-0 rounded-4 p-3 bg-light-subtle">
                    <div class="card-body">
                        <h5 class="card-title fw-semibold text-primary">
                            📦 {{ $claim->request->item_name ?? '-' }}
                        </h5>
                        <p class="mb-1"><strong>Quantity:</strong> {{ $claim->request->quantity ?? '-' }}</p>
                        <p class="mb-1"><strong>Status:</strong>
                            <span class="badge bg-warning text-dark">{{ ucfirst($claim->status) }}</span>
                        </p>
                        <p class="text-muted small">🕒 Claimed at: {{ $claim->created_at->format('d M Y, h:i A') }}</p>
                    </div>
                    <div class="card-footer bg-transparent border-0">
                        <a href="{{ route('requests.show', ['id' => $claim->request->id, 'from' => 'claimed']) }}">View Details</a>


                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info text-center rounded-4">
                    You haven't claimed any requests yet.
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection
