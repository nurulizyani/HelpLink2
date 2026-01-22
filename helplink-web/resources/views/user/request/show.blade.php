@php
    use App\Models\Claim;

    $isMyRequest = $request->user_id === auth()->id();
    $fromClaimed = request('from') === 'claimed';

    if ($isMyRequest) {
        $userClaim = Claim::where('request_id', $request->id)->first();
    } else {
        $userClaim = Claim::where('request_id', $request->id)
            ->where('user_id', auth()->id())
            ->first();
    }

    $alreadyClaimed = $userClaim !== null;
@endphp

@extends('layouts.app')

@section('content')
<div class="container py-4">

    {{-- 🔙 Back Button --}}
    @if ($isMyRequest)
        <a href="{{ route('requests.my') }}" class="btn btn-outline-secondary mb-3">← Back to My Requests</a>
    @elseif ($fromClaimed)
        <a href="{{ route('claims.request.my') }}" class="btn btn-outline-secondary mb-3">← Back to Claimed Requests</a>
    @else
        <a href="{{ route('claims.request.available') }}" class="btn btn-outline-secondary mb-3">← Back to Available Requests</a>
    @endif

    {{-- Main Card --}}
    <div class="card shadow rounded-4 p-4">
        <h3 class="fw-bold mb-4 text-primary">
            <i class="fas fa-box-open me-2 text-warning"></i> {{ $request->item_name }}
        </h3>

        <div class="row mb-4">
            <div class="col-md-6">

                <p><strong>Story / Description:</strong><br>{{ $request->description }}</p>
                <p><strong>Quantity:</strong> {{ $request->quantity }}</p>
                <p><strong>Delivery Type:</strong> {{ ucfirst($request->delivery_type) }}</p>
                <p><strong>Address:</strong> {{ $request->address }}</p>
                <p><strong>Location:</strong> {{ $request->location }}</p>
                <p><strong>Status:</strong>
                    <span class="badge bg-{{ $request->status === 'claimed' ? 'info' : ($request->status === 'completed' ? 'primary' : 'secondary') }}">
                        {{ ucfirst($request->status) }}
                    </span>
                </p>

                {{-- 💬 Chat button displayed here like in show offer --}}
                @php
                    $chatTargetId = $isMyRequest ? ($userClaim->user_id ?? null) : $request->user_id;
                @endphp
                @if (!$isMyRequest || ($isMyRequest && $userClaim))
                    <a href="{{ route('chat.show', $chatTargetId) }}" class="btn btn-outline-dark w-100 mt-3 rounded-pill">
                        💬 Chat with {{ $isMyRequest ? 'Recipient' : 'Requester' }}
                    </a>
                @endif

                {{-- 📍 Google Maps + Distance --}}
                @if($request->latitude && $request->longitude)
                    <div class="mt-3 mb-2">
                        <a href="https://www.google.com/maps?q={{ $request->latitude }},{{ $request->longitude }}" target="_blank" class="me-2 text-decoration-none">
                            📍 View on Google Maps
                        </a>
                        |
                        <a href="https://www.google.com/maps/dir/?api=1&destination={{ $request->latitude }},{{ $request->longitude }}" target="_blank" class="ms-2 text-decoration-none">
                            🚗 Get Directions
                        </a>
                    </div>

                    <div class="rounded-4 overflow-hidden shadow-sm mb-3" style="height: 280px;">
                        <iframe
                            width="100%"
                            height="100%"
                            style="border: 0"
                            loading="lazy"
                            allowfullscreen
                            referrerpolicy="no-referrer-when-downgrade"
                            src="https://www.google.com/maps?q={{ $request->latitude }},{{ $request->longitude }}&output=embed">
                        </iframe>
                    </div>

                    <p id="distance-info" class="text-muted fst-italic">📏 Calculating distance from your current position...</p>
                @endif
            </div>

            {{-- 📎 Supporting Documents --}}
            @if($request->images->count())
                <div class="col-md-6">
                    <h6 class="fw-semibold">Supporting Documents</h6>
                    <div class="row">
                        @foreach($request->images as $image)
                            <div class="col-6 mb-3">
                                <img src="{{ asset('storage/' . $image->image_path) }}" class="img-fluid rounded-3 shadow-sm" alt="Document">
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- ✅ Action Buttons --}}
        <div class="mt-4">
            @if ($isMyRequest && $userClaim && $userClaim->status === 'pending')
                <form action="{{ route('claims.request.complete', $userClaim->id) }}" method="POST" onsubmit="return confirm('Mark this request as completed?');">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-primary w-100 rounded-pill">✅ Mark as Completed</button>
                </form>
            @endif

            @if (!$isMyRequest && !$alreadyClaimed)
                <form action="{{ route('claims.request.claim', $request->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to claim this request?');">
                    @csrf
                    <button type="submit" class="btn btn-success w-100 mt-3 rounded-pill">🤝 Claim This Request</button>
                </form>
            @endif

            @if ($userClaim && $userClaim->status === 'completed' && $userClaim->rating === null)
                <a href="{{ route('claims.request.rate', $userClaim->id) }}" class="btn btn-warning w-100 mt-3 rounded-pill">🌟 Rate This Request</a>
            @endif
        </div>
    </div>
</div>
@endsection

{{-- Distance Calculation --}}
@section('scripts')
@if($request->latitude && $request->longitude)
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const distanceInfo = document.getElementById("distance-info");

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function (position) {
                    const userLat = position.coords.latitude;
                    const userLon = position.coords.longitude;
                    const reqLat = {{ $request->latitude }};
                    const reqLon = {{ $request->longitude }};

                    const R = 6371;
                    const dLat = (reqLat - userLat) * Math.PI / 180;
                    const dLon = (reqLon - userLon) * Math.PI / 180;
                    const a = Math.sin(dLat / 2) ** 2 +
                              Math.cos(userLat * Math.PI / 180) *
                              Math.cos(reqLat * Math.PI / 180) *
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
