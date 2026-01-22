@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-4">My Claimed Offers</h3>

    <div class="row">
    @forelse($claims as $claim)
        @if(!$claim->offer)
            @continue
        @endif

        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">{{ $claim->offer->item_name }}</h5>
                    <p class="card-text text-muted small">{{ $claim->offer->description ?? '-' }}</p>

                    <p class="mb-1">
                        <strong>Status:</strong>
                        @switch(strtolower($claim->status))
                            @case('pending')
                                <span class="badge bg-warning text-dark">Pending</span>
                                @break
                            @case('completed')
                                <span class="badge bg-success">Completed</span>
                                @break
                            @case('approved')
                                <span class="badge bg-primary">Approved</span>
                                @break
                            @case('rejected')
                                <span class="badge bg-danger">Rejected</span>
                                @break
                            @default
                                <span class="badge bg-secondary">{{ ucfirst($claim->status) }}</span>
                        @endswitch
                    </p>

                    <p class="mb-1"><strong>Claimed at:</strong> {{ $claim->created_at->format('d M Y, h:i A') }}</p>

                    <p class="mb-1">
                        <strong>Location:</strong>
                        {{ Auth::user()->address ?? 'No address available' }}
                    </p>

                    @if($claim->rating)
                        <p class="mb-1"><strong>Rating:</strong> {{ $claim->rating }} ★</p>
                        <p><strong>Comment:</strong> {{ $claim->comment }}</p>
                    @else
                        <form action="{{ route('claims.rate', $claim->id) }}" method="GET">
                            <button class="btn btn-sm btn-outline-success mt-2">⭐ Rate Now</button>
                        </form>
                    @endif

            
@if($claim->offer_id)
    <a href="{{ route('offer.show', ['id' => $claim->offer_id]) }}" class="btn btn-sm btn-outline-primary mt-2">
        📄 View Offer Details
    </a>
@else
    <span class="text-muted">Offer no longer available</span>
@endif

                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-info">You have not claimed any offers yet.</div>
        </div>
    @endforelse
    </div>
</div>
@endsection
