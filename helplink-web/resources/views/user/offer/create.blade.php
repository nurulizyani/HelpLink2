@extends('layouts.app')

@section('content')
<div class="container py-5">
    @include('includes.alert')
    <div class="card shadow-lg border-0 rounded-4 p-5 bg-white">
        <h2 class="mb-4 text-center fw-bold text-primary"> Create New Offer</h2>

        {{-- Display Validation Errors --}}
        @if ($errors->any())
            <div class="alert alert-danger rounded-4 shadow-sm">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li><i class="fa fa-circle-exclamation me-1 text-danger"></i> {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('offer.store') }}" method="POST" enctype="multipart/form-data">

            @csrf

            {{-- Item Name --}}
            <div class="mb-3">
                <label for="item_name" class="form-label fw-semibold">Item Name</label>
                <input type="text" name="item_name" id="item_name" class="form-control rounded-4 shadow-sm" required autocomplete="off" placeholder="e.g. Rice, Bread, etc.">
            </div>

            {{-- Description --}}
            <div class="mb-3">
                <label for="description" class="form-label fw-semibold">Description</label>
                <textarea name="description" id="description" class="form-control rounded-4 shadow-sm" rows="3" placeholder="Short description (optional)" autocomplete="off"></textarea>
            </div>

            {{-- Offer Image --}}
            <div class="mb-3">
                <label for="image" class="form-label fw-semibold">Offer Image (optional)</label>
                <input type="file" name="image" id="image" class="form-control rounded-4 shadow-sm">
            </div>


            {{-- Quantity --}}
            <div class="mb-3">
                <label for="quantity" class="form-label fw-semibold">Quantity</label>
                <input type="number" name="quantity" id="quantity" class="form-control rounded-4 shadow-sm" min="1" placeholder="e.g. 10" autocomplete="off">
            </div>

            {{-- Full Address --}}
            <div class="mb-3">
                <label for="address" class="form-label fw-semibold">Full Address</label>
                <textarea name="address" id="address" class="form-control rounded-4 shadow-sm" rows="2" placeholder="e.g. No 123, Jalan Bunga, 75000 Melaka" autocomplete="off"></textarea>
            </div>

            {{-- Location --}}
            <div class="mb-3">
                <label for="location" class="form-label fw-semibold">Location (Area / Taman / Apartment)</label>
                <input type="text" class="form-control rounded-4 shadow-sm" id="location" name="location" placeholder="e.g. Taman Sri Mawar" autocomplete="off">
            </div>

            {{-- Delivery Type --}}
            <div class="mb-3">
                <label for="delivery_type" class="form-label fw-semibold">Delivery Type</label>
                <select name="delivery_type" id="delivery_type" class="form-select rounded-4 shadow-sm">
                    <option value="">-- Select --</option>
                    <option value="pickup">Pickup</option>
                    <option value="delivery">Delivery</option>
                </select>
            </div>

            {{-- Location Button --}}
            <div class="mb-4">
                <button type="button" id="useLocationBtn" class="btn btn-outline-info btn-sm rounded-pill shadow-sm px-4">
                    📍 Use My Current Location
                </button>
            </div>

            {{-- Hidden Lat/Lon --}}
            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">

            {{-- Submit --}}
            <div class="text-center mt-4">
                <button type="submit" class="btn btn-primary px-5 py-2 rounded-pill shadow-sm">
                    <i class="fa fa-paper-plane me-1"></i> Submit Offer
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function getCurrentLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function (position) {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;

                    // Simpan lat/lon
                    document.getElementById('latitude').value = lat;
                    document.getElementById('longitude').value = lon;

                    // Reverse geocode guna OpenStreetMap
                    fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lon}&format=json`)
                        .then(response => response.json())
                        .then(data => {
                            // Auto isi alamat penuh
                            document.getElementById('address').value = data.display_name || '';

                            // Cuba detect lokasi kawasan (area/taman)
                            const addr = data.address;
                            let area =
                                addr.suburb ||
                                addr.neighbourhood ||
                                addr.village ||
                                addr.town ||
                                addr.city_district ||
                                addr.city ||
                                addr.state_district ||
                                '';

                            document.getElementById('location').value = area || "(Unknown Area)";
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
            alert("Geolocation not supported.");
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
