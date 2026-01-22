@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h3 class="mb-4 text-primary fw-bold">📝 Create Request</h3>

    @include('includes.alert')

    {{-- Validation errors --}}
    @if ($errors->any())
        <div class="alert alert-danger rounded-4 shadow-sm">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li><i class="fa fa-circle-exclamation me-1 text-danger"></i> {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('requests.store') }}" method="POST" enctype="multipart/form-data" class="card shadow-sm p-4 rounded-4 border-0 bg-white">
        @csrf

        {{-- Item Name --}}
        <div class="mb-3">
            <label for="item_name" class="form-label fw-semibold">Item Name</label>
            <input type="text" name="item_name" class="form-control" required>
        </div>

        {{-- Description --}}
        <div class="mb-3">
            <label for="description" class="form-label fw-semibold">Story / Description</label>
            <textarea name="description" class="form-control" rows="4" placeholder="Explain your current situation or why you need help..." required></textarea>
        </div>

        {{-- Quantity --}}
        <div class="mb-3">
            <label for="quantity" class="form-label fw-semibold">Quantity</label>
            <input type="number" name="quantity" class="form-control" required min="1">
        </div>

        {{-- Delivery Type --}}
        <div class="mb-3">
            <label for="delivery_type" class="form-label fw-semibold">Delivery Type</label>
            <select name="delivery_type" class="form-select" required>
                <option value="pickup">Pickup</option>
                <option value="delivery">Delivery</option>
            </select>
        </div>

        {{-- Address --}}
        <div class="mb-3">
            <label for="address" class="form-label fw-semibold">Address</label>
            <input type="text" name="address" class="form-control" placeholder="e.g. Jalan Mawar 5, Taman Bukit" required>
        </div>

        {{-- Location --}}
        <div class="mb-3">
            <label for="location" class="form-label fw-semibold">Location (Area / City)</label>
            <input type="text" name="location" class="form-control" placeholder="e.g. Melaka / Johor Bahru" required>
        </div>

        {{-- Location Button --}}
        <div class="mb-3">
            <button type="button" id="useLocationBtn" class="btn btn-outline-info btn-sm rounded-pill shadow-sm px-4">
                📍 Use My Current Location
            </button>
        </div>


        {{-- Geolocation --}}
        <input type="hidden" name="latitude" id="latitude">
        <input type="hidden" name="longitude" id="longitude">

        {{-- Upload Supporting Images --}}
        <div class="mb-3">
            <label for="supporting_documents" class="form-label">Upload Supporting Documents (Image only)</label>
            <input type="file" name="supporting_documents[]" class="form-control" accept="image/*" multiple>
            <small class="text-muted">Upload relevant documents to support your request (e.g. salary slip, OKU card, support letter). Max 2MB per file.</small>
        </div>

        {{-- Submit --}}
        <button type="submit" class="btn btn-primary w-100 rounded-4 fw-semibold">Submit Request</button>
    </form>
</div>

{{-- Auto detect location --}}
<script>
    function getCurrentLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function (position) {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;

                    // Set hidden input
                    document.getElementById('latitude').value = lat;
                    document.getElementById('longitude').value = lon;

                    // Reverse Geocode using OpenStreetMap
                    fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lon}&format=json`)
                        .then(response => response.json())
                        .then(data => {
                            // Auto fill address
                            document.querySelector('input[name="address"]').value = data.display_name || '';

                            // Try to extract area
                            const addr = data.address;
                            let area = addr.suburb || addr.neighbourhood || addr.village || addr.town || addr.city || addr.state || '';
                            document.querySelector('input[name="location"]').value = area || "(Unknown Area)";
                        })
                        .catch(() => {
                            alert("Failed to retrieve address.");
                        });
                },
                function (error) {
                    alert("Error getting location: " + error.message);
                }
            );
        } else {
            alert("Geolocation is not supported.");
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        const btn = document.getElementById('useLocationBtn');
        if (btn) {
            btn.addEventListener('click', getCurrentLocation);
        }
    });
</script>

@endsection
