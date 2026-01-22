@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="mb-4 fw-bold"><i class="bi bi-box-seam"></i> My Offers</h2>

    <form method="GET" class="mb-4 d-flex align-items-center gap-2">
        <label class="fw-semibold">Filter by Status:</label>
        <select name="status" class="form-select w-auto">
            <option value="">All Status</option>
            <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>Available</option>
            <option value="claimed" {{ request('status') == 'claimed' ? 'selected' : '' }}>Claimed</option>
            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
        </select>
        <button class="btn btn-primary">Filter</button>
    </form>

    <div class="row g-3">
        @forelse ($offers as $offer)
            <div class="col-md-6 col-lg-4">
                <div class="border rounded shadow-sm p-3 h-100 position-relative">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="fw-semibold text-dark">{{ strtoupper($offer->item_name) }}</h5>

                        <span class="badge bg-{{ $offer->status == 'available' ? 'success' : ($offer->status == 'claimed' ? 'warning text-dark' : 'secondary') }}">
                            {{ ucfirst($offer->status) }}
                        </span>
                    </div>
                    <p class="text-muted">{{ $offer->description }}</p>

                    <p class="mb-1"><i class="bi bi-geo-alt-fill text-danger"></i> <strong>Location:</strong> {{ $offer->location ?? '-' }}</p>
                    <a href="https://www.google.com/maps/search/?api=1&query={{ $offer->latitude }},{{ $offer->longitude }}" target="_blank" class="d-block mb-2 text-decoration-none">View on Map</a>

                    <p class="mb-1"><i class="bi bi-box-fill text-warning"></i> <strong>Quantity:</strong> {{ $offer->quantity }}</p>
                    <p class="mb-1"><i class="bi bi-truck text-dark"></i> <strong>Delivery:</strong> {{ ucfirst($offer->delivery_type) }}</p>
                    <p class="mb-3"><i class="bi bi-star-fill text-warning"></i> <strong>Rating:</strong>
                        @if ($offer->ratings && $offer->ratings->count() > 0)
                            {{ number_format($offer->ratings->avg('rating'), 1) }} ★ ({{ $offer->ratings->count() }} ratings)
                        @else
                            No ratings yet
                        @endif
                    </p>

                    <div class="d-flex justify-content-between gap-2 mt-auto">
                        <a href="{{ route('offer.show', $offer->offer_id) }}" class="btn btn-sm btn-outline-primary w-100">View</a>
                        <a href="{{ route('offer.edit', $offer->offer_id) }}" class="btn btn-sm btn-warning w-100">Edit</a>
                        <form action="{{ route('offer.destroy', $offer->offer_id) }}" method="POST" class="w-100">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger w-100" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <p>No offers found.</p>
        @endforelse
    </div>
</div>
@endsection
