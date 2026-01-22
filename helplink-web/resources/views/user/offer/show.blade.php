@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-lg border-0 rounded-4">
        <div class="card-body p-4">

            {{-- Title --}}
            <h3 class="fw-bold text-primary mb-4">
                📦 Offer Details
            </h3>

            {{-- Offer Image (if exists) --}}
            @if($offer->image)
                <div class="mb-4 text-center">
                    <img src="{{ asset('storage/'.$offer->image) }}" alt="Offer Image" class="img-fluid rounded-4 shadow-sm" style="max-height: 300px;">
                </div>
            @endif

            {{-- Item Name --}}
            <h4 class="fw-semibold">{{ $offer->item_name }}</h4>

            {{-- Basic Details --}}
            <p><strong>Description:</strong> {{ $offer->description ?? '-' }}</p>
            <p><strong>Quantity:</strong> {{ $offer->quantity ?? '-' }}</p>

            {{-- Delivery Type --}}
            <p><strong>Delivery Type:</strong> 
                @if($offer->delivery_type === 'pickup')
                    <span class="badge bg-warning text-dark px-3 py-1">Pickup</span>
                @elseif($offer->delivery_type === 'delivery')
                    <span class="badge bg-success px-3 py-1">Delivery</span>
                @else
                    <span class="badge bg-secondary px-3 py-1">-</span>
                @endif
            </p>

            {{-- Address Info --}}
            <p><strong>Full Address:</strong> {{ $offer->address ?? '-' }}</p>
            <p><strong>Location (Area):</strong> {{ $offer->location ?? '-' }}</p>

            {{-- Chat Button --}}
            @if($offer->user_id !== auth()->id())
                <div class="my-3">
                    <a href="{{ route('chat.show', $offer->user_id) }}" class="btn btn-outline-primary px-4 py-2 rounded-pill">
                        💬 Chat with {{ $offer->user->name }}
                    </a>
                </div>
            @endif

            {{-- Google Map + Direction --}}
            @if($offer->latitude && $offer->longitude)
                <div class="mb-3">
                    <a href="https://www.google.com/maps?q={{ $offer->latitude }},{{ $offer->longitude }}"
                       class="me-2 text-decoration-none" target="_blank">📍 View on Google Maps</a>
                    |
                    <a href="https://www.google.com/maps/dir/?api=1&destination={{ $offer->latitude }},{{ $offer->longitude }}"
                       class="ms-2 text-decoration-none" target="_blank">🚗 Get Directions</a>
                </div>

                {{-- Embedded Map --}}
                <div class="rounded-4 overflow-hidden shadow-sm mb-3" style="height: 300px;">
                    <iframe
                        width="100%"
                        height="100%"
                        style="border: 0"
                        loading="lazy"
                        allowfullscreen
                        referrerpolicy="no-referrer-when-downgrade"
                        src="https://www.google.com/maps?q={{ $offer->latitude }},{{ $offer->longitude }}&output=embed">
                    </iframe>
                </div>

                {{-- Distance Display --}}
                <p id="distance-info" class="text-muted fst-italic">📏 Calculating distance from you...</p>
            @endif

            {{-- Status & Posted Time --}}
            <p><strong>Status:</strong> 
                <span class="badge bg-info text-dark px-3 py-1">{{ ucfirst($offer->status) }}</span>
            </p>
            <p><strong>Posted on:</strong> {{ $offer->created_at->format('d M Y, h:i A') }}</p>

            {{-- Claim Button --}}
            @if($offer->status === 'available' && $offer->user_id !== auth()->id())
                <form action="{{ route('offers.claim', $offer->offer_id) }}" method="POST" class="mt-4">
                    @csrf
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-pill">
                        🤝 Claim This Offer
                    </button>
                </form>
            @endif

            {{-- Back Button --}}
            <div class="mt-4">
            @php
                    $from = request('from');
                    $backUrl = match($from) {
                        'my' => route('offer.my'),
                        'available' => route('offers.available'),
                        'claimed' => route('claims.index'),
                        default => url()->previous(),
                    };
                @endphp

                <a href="{{ $backUrl }}" class="btn btn-secondary px-4 py-2 rounded-pill">
                    ← Back to Offers
                </a>
            </div>
`
        </div>
    </div>
</div>
@endsection

@section('scripts')
@if($offer->latitude && $offer->longitude)
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const distanceInfo = document.getElementById("distance-info");

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function (position) {
                    const userLat = position.coords.latitude;
                    const userLon = position.coords.longitude;
                    const offerLat = {{ $offer->latitude }};
                    const offerLon = {{ $offer->longitude }};

                    const R = 6371;
                    const dLat = (offerLat - userLat) * Math.PI / 180;
                    const dLon = (offerLon - userLon) * Math.PI / 180;
                    const a = Math.sin(dLat / 2) ** 2 +
                              Math.cos(userLat * Math.PI / 180) *
                              Math.cos(offerLat * Math.PI / 180) *
                              Math.sin(dLon / 2) ** 2;
                    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                    const distance = (R * c).toFixed(2);

                    distanceInfo.innerText = `📏 This location is about ${distance} km from your current position.`;
                },
                function (error) {
                    console.warn("Geolocation error:", error);
                    distanceInfo.innerText = "📏 Unable to detect your current location.";
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        } else {
            distanceInfo.innerText = "📏 Geolocation is not supported by your browser.";
        }
    });
</script>
@endif
@endsection
