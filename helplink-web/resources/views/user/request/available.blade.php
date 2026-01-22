@extends('layouts.app')

@section('title', 'Available Requests')

@section('content')
<div class="container py-4">
    <h3 class="mb-4 fw-bold text-primary">📦 Available Approved Requests</h3>

    {{-- 🔎 Filter by Location --}}
    <form method="GET" action="{{ route('claims.request.available') }}" class="mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-6">
                <label for="location" class="form-label">Filter by Location</label>
                <select name="location" id="location" class="form-select">
                    <option value="">-- All Locations --</option>
                    @foreach ($locations as $loc)
                        <option value="{{ $loc }}" {{ request('location') == $loc ? 'selected' : '' }}>
                            {{ $loc }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-filter-circle me-1"></i> Filter
                </button>
            </div>
        </div>
    </form>

    {{-- ✅ Request Cards --}}
    <div class="row">
        @forelse ($requests as $request)
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 shadow-sm rounded-4">
                    @if ($request->images->count())
                        <img src="{{ asset('storage/' . $request->images->first()->image_path) }}"
                             class="card-img-top rounded-top" alt="Request Image"
                             style="height: 200px; object-fit: cover;">
                    @else
                        <img src="{{ asset('img/default-request.jpg') }}" class="card-img-top rounded-top"
                             alt="Default Image" style="height: 200px; object-fit: cover;">
                    @endif
                    <div class="card-body">
                        <h5 class="fw-bold">{{ $request->item_name }}</h5>
                        <p class="mb-1"><strong>Quantity:</strong> {{ $request->quantity }}</p>
                        <p class="mb-1"><strong>Delivery:</strong> {{ ucfirst($request->delivery_type) }}</p>
                        <p class="mb-1"><strong>Location:</strong> {{ $request->location }}</p>

                        @if($request->latitude && $request->longitude)
                            <div class="text-muted small distance-info"
                                data-lat="{{ $request->latitude }}"
                                data-lng="{{ $request->longitude }}">
                                📍 Calculating distance...
                            </div>
                        @endif

                        <p class="text-muted small mb-0">Requested by: {{ $request->user->name }}</p>
                    </div>
                    <div class="card-footer bg-transparent border-top-0 d-flex justify-content-between align-items-center">
                        @if($request->latitude && $request->longitude)
                            <a href="https://www.google.com/maps?q={{ $request->latitude }},{{ $request->longitude }}"
                               target="_blank" class="text-decoration-none small text-primary">
                               <i class="fas fa-map-pin me-1"></i> Google Maps
                            </a>
                        @endif

                        <a href="{{ route('requests.show', $request->id) }}"
                           onclick="event.preventDefault(); 
                                    localStorage.setItem('return_to', '{{ route('claims.request.available') }}');
                                    window.location.href=this.href;">
                            View Details
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info rounded-3 shadow-sm">
                    <i class="bi bi-info-circle me-1"></i> No approved requests available at the moment.
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener("DOMContentLoaded", () => {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function (position) {
                const userLat = position.coords.latitude;
                const userLng = position.coords.longitude;

                document.querySelectorAll('.distance-info').forEach(el => {
                    const requestLat = el.dataset.lat;
                    const requestLng = el.dataset.lng;
                    const distance = haversineDistance(userLat, userLng, requestLat, requestLng);
                    el.textContent = `📍 ${distance.toFixed(2)} km from your location`;
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

        function toRad(value) {
            return value * Math.PI / 180;
        }
    });
</script>
@endsection
