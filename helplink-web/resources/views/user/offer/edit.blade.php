@extends('layouts.app')

@section('content')
<div class="container py-5">
    @include('includes.alert')
    <div class="card shadow-lg border-0 rounded-4 p-5 bg-white">
        <h2 class="mb-4 text-center fw-bold text-primary">✏️ Edit Offer</h2>

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

        <form method="POST" action="{{ route('offer.update', $offer->offer_id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- Item Name --}}
            <div class="mb-3">
                <label for="item_name" class="form-label fw-semibold">Item Name</label>
                <input type="text" name="item_name" id="item_name" class="form-control rounded-4 shadow-sm"
                       value="{{ $offer->item_name }}" required>
            </div>

            {{-- Description --}}
            <div class="mb-3">
                <label for="description" class="form-label fw-semibold">Description</label>
                <textarea name="description" id="description" class="form-control rounded-4 shadow-sm"
                          rows="3">{{ $offer->description }}</textarea>
            </div>

            {{-- Quantity --}}
            <div class="mb-3">
                <label for="quantity" class="form-label fw-semibold">Quantity</label>
                <input type="number" name="quantity" id="quantity" class="form-control rounded-4 shadow-sm"
                       value="{{ $offer->quantity }}" min="1">
            </div>

            {{-- Address --}}
            <div class="mb-3">
                <label for="address" class="form-label fw-semibold">Full Address</label>
                <input type="text" name="address" id="address" class="form-control rounded-4 shadow-sm"
                       value="{{ $offer->address }}">
            </div>

            {{-- Location --}}
            <div class="mb-3">
                <label for="location" class="form-label fw-semibold">Location (Area / Taman / Apartment)</label>
                <input type="text" name="location" id="location" class="form-control rounded-4 shadow-sm"
                       value="{{ $offer->location ?? '' }}" placeholder="e.g. Taman Sri Mawar">
            </div>

            {{-- Delivery Type --}}
            <div class="mb-3">
                <label for="delivery_type" class="form-label fw-semibold">Delivery Type</label>
                <select name="delivery_type" id="delivery_type" class="form-select rounded-4 shadow-sm">
                    <option value="">-- Select --</option>
                    <option value="pickup" {{ $offer->delivery_type == 'pickup' ? 'selected' : '' }}>Pickup</option>
                    <option value="delivery" {{ $offer->delivery_type == 'delivery' ? 'selected' : '' }}>Delivery</option>
                </select>
            </div>

            {{-- Existing Image --}}
            @if($offer->image)
            <div class="mb-3 text-center">
                <label class="form-label fw-semibold d-block">Current Image</label>
                <img src="{{ asset('storage/'.$offer->image) }}" alt="Current Image" class="img-fluid rounded shadow-sm" style="max-height: 200px;">
            </div>
            @endif

            {{-- Upload New Image --}}
            <div class="mb-3">
                <label for="image" class="form-label fw-semibold">Upload New Image (optional)</label>
                <input type="file" name="image" id="image" class="form-control rounded-4 shadow-sm" accept="image/*">
                <small class="text-muted">Leave this empty to keep the current image.</small>
            </div>

            {{-- Buttons --}}
            <div class="mt-4 text-center">
                <button type="submit" class="btn btn-primary px-5 py-2 rounded-pill shadow-sm me-2">
                    <i class="fa fa-save me-1"></i> Update
                </button>
                <a href="{{ route('offer.my') }}" class="btn btn-secondary px-4 py-2 rounded-pill shadow-sm">
                    <i class="fa fa-times me-1"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
