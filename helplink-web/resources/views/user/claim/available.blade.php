@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h3 class="mb-4"><i class="fas fa-hand-holding-heart me-1"></i> Available Offers to Claim</h3>

    {{-- Filter Form --}}
    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-4">
            <label for="delivery_type" class="form-label fw-semibold">Filter by Delivery Type</label>
            <select name="delivery_type" id="delivery_type" class="form-select">
                <option value="">All</option>
                <option value="pickup" {{ request('delivery_type') === 'pickup' ? 'selected' : '' }}>Pickup</option>
                <option value="delivery" {{ request('delivery_type') === 'delivery' ? 'selected' : '' }}>Delivery</option>
            </select>
        </div>

        <div class="col-md-4">
            <label for="location" class="form-label fw-semibold">Filter by Location</label>
            <input type="text" name="location" id="location" class="form-control"
                placeholder="Enter location (e.g. Jasin, Melaka)"
                value="{{ request('location') }}">
        </div>

        <div class="col-md-4 align-self-end">
            <button type="submit" class="btn btn-sm btn-primary w-100">
                <i class="fas fa-filter me-1"></i> Apply Filters
            </button>
        </div>
    </form>

    {{-- Flash Message --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Offer List --}}
    @if($offers->isEmpty())
        <div class="alert alert-info">No available offers at the moment.</div>
    @else
        <div class="row row-cols-1 row-cols-md-2 g-4">
            @foreach($offers as $offer)
            <div class="col">
                <div class="card h-100 shadow-sm border-0 rounded-4">

                    {{-- Offer Image --}}
                    @if($offer->image)
                        <img src="{{ asset('storage/' . $offer->image) }}" alt="Offer Image"
                            class="card-img-top rounded-top-4"
                            style="height: 200px; width: 100%; object-fit: contain;">
                    @else
                        <img src="{{ asset('images/default-offer.png') }}" alt="No Image"
                            class="card-img-top rounded-top-4"
                            style="height: 200px; width: 100%; object-fit: contain;">
                    @endif


                    <div class="card-body d-flex flex-column justify-content-between">
                        {{-- Header --}}
                        <div class="mb-2">
                            <h5 class="card-title fw-bold mb-1">{{ $offer->item_name }}</h5>
                            <span class="badge bg-light text-dark border border-info small">
                                {{ ucfirst($offer->delivery_type ?? '-') }}
                            </span>
                        </div>

                        {{-- Location --}}
                        <div class="mb-2 small">
                            <i class="fas fa-map-marker-alt text-danger me-1"></i>
                            {{ Str::limit($offer->address ?? '-', 60) }}
                            @if($offer->latitude && $offer->longitude)
                                <div class="text-muted distance-info"
                                    data-lat="{{ $offer->latitude }}"
                                    data-lng="{{ $offer->longitude }}">
                                    📍 Calculating distance...
                                </div>
                            @endif
                        </div>

                        {{-- Action --}}
                        <div class="mt-3 d-flex justify-content-between align-items-center">
                            <a href="https://www.google.com/maps?q={{ $offer->latitude }},{{ $offer->longitude }}"
                                target="_blank" class="text-decoration-none small text-primary">
                                <i class="fas fa-map-pin me-1"></i> Google Maps
                            </a>

                            <a href="{{ route('offer.show', $offer->offer_id) }}"
                               class="btn btn-outline-primary btn-sm rounded-pill">
                                <i class="fas fa-eye me-1"></i> View More
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>

{{-- Geolocation Script --}}
<script>
    document.addEventListener("DOMContentLoaded", () => {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function (position) {
                const userLat = position.coords.latitude;
                const userLng = position.coords.longitude;

                document.querySelectorAll('.distance-info').forEach(el => {
                    const offerLat = el.dataset.lat;
                    const offerLng = el.dataset.lng;
                    const distance = haversineDistance(userLat, userLng, offerLat, offerLng);
                    el.textContent = `📍 ${distance.toFixed(2)} km from your current location`;
                });
            });
        }

        function haversineDistance(lat1, lon1, lat2, lon2) {
            const R = 6371;
            const dLat = toRad(lat2 - lat1);
            const dLon = toRad(lon2 - lon1);
            const a = Math.sin(dLat / 2) ** 2 +
                      Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
                      Math.sin(dLon / 2) ** 2;
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c;
        }

        function toRad(Value) {
            return Value * Math.PI / 180;
        }
    });
</script>
@endsection
