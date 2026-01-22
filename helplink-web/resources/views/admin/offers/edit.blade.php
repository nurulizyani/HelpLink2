@extends('layouts.admin')

@section('title', 'Edit Offer')

@section('content')
<div class="container">
    <h2 class="mb-4 fw-bold text-primary"><i class="fas fa-edit me-2"></i>Edit Offer</h2>

    {{-- Back Button --}}
    <a href="{{ route('admin.offers.index') }}" class="btn btn-secondary mb-3">
        <i class="fas fa-arrow-left me-1"></i> Back to All Offers
    </a>

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="alert alert-danger rounded-3">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li><i class="fas fa-circle-exclamation me-2 text-danger"></i>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Edit Form --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.offers.update', $offer->offer_id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                {{-- Item Name --}}
                <div class="mb-3">
                    <label for="item_name" class="form-label fw-semibold">Item Name</label>
                    <input type="text" name="item_name" id="item_name" class="form-control"
                           value="{{ old('item_name', $offer->item_name) }}" required>
                </div>

                {{-- Description --}}
                <div class="mb-3">
                    <label for="description" class="form-label fw-semibold">Description</label>
                    <textarea name="description" id="description" class="form-control"
                              rows="3">{{ old('description', $offer->description) }}</textarea>
                </div>

                {{-- Quantity --}}
                <div class="mb-3">
                    <label for="quantity" class="form-label fw-semibold">Quantity</label>
                    <input type="number" name="quantity" id="quantity" class="form-control"
                           value="{{ old('quantity', $offer->quantity) }}" min="1">
                </div>

                {{-- Delivery Type --}}
                <div class="mb-3">
                    <label for="delivery_type" class="form-label fw-semibold">Delivery Type</label>
                    <select name="delivery_type" id="delivery_type" class="form-select">
                        <option value="pickup" {{ $offer->delivery_type == 'pickup' ? 'selected' : '' }}>Pickup</option>
                        <option value="delivery" {{ $offer->delivery_type == 'delivery' ? 'selected' : '' }}>Delivery</option>
                    </select>
                </div>

                {{-- Address --}}
                <div class="mb-3">
                    <label for="address" class="form-label fw-semibold">Address</label>
                    <textarea name="address" id="address" class="form-control" rows="2">{{ old('address', $offer->address) }}</textarea>
                </div>

                {{-- Image Preview --}}
                @if ($offer->image)
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Current Image</label><br>
                        <img src="{{ asset('storage/' . $offer->image) }}" alt="Offer Image"
                             class="rounded shadow-sm" style="max-height: 150px;">
                    </div>
                @endif

                {{-- Image Upload --}}
                <div class="mb-3">
                    <label for="image" class="form-label fw-semibold">Update Image</label>
                    <input type="file" name="image" id="image" class="form-control">
                </div>

                {{-- Status --}}
                <div class="mb-3">
                    <label for="status" class="form-label fw-semibold">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="available" {{ $offer->status == 'available' ? 'selected' : '' }}>Available</option>
                        <option value="claimed" {{ $offer->status == 'claimed' ? 'selected' : '' }}>Claimed</option>
                        <option value="completed" {{ $offer->status == 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>

                {{-- Submit Button --}}
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save me-1"></i> Update Offer
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
