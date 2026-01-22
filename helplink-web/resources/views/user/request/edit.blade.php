@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4">

                    <h3 class="mb-4 text-primary fw-bold text-center">✏️ Edit Your Request</h3>

                    @include('includes.alert')

                    @if ($errors->any())
                        <div class="alert alert-danger rounded-4 shadow-sm">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li><i class="fa fa-circle-exclamation me-1 text-danger"></i> {{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('requests.update', $request->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Item Name</label>
                            <input type="text" name="item_name" class="form-control" value="{{ old('item_name', $request->item_name) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Story / Description</label>
                            <textarea name="description" class="form-control" rows="4" required>{{ old('description', $request->description) }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" class="form-control" value="{{ old('quantity', $request->quantity) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Delivery Type</label>
                            <select name="delivery_type" class="form-select" required>
                                <option value="pickup" {{ $request->delivery_type == 'pickup' ? 'selected' : '' }}>Pickup</option>
                                <option value="delivery" {{ $request->delivery_type == 'delivery' ? 'selected' : '' }}>Delivery</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" value="{{ old('address', $request->address) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Location (Area/City)</label>
                            <input type="text" name="location" class="form-control" value="{{ old('location', $request->location) }}" required>
                        </div>

                        <input type="hidden" name="latitude" value="{{ $request->latitude }}">
                        <input type="hidden" name="longitude" value="{{ $request->longitude }}">

                        {{-- Image Preview (Safe & Clean) --}}
                        @if (!empty($request->images) && $request->images->count())
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Current Images:</label>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ($request->images as $image)
                                        <div class="text-center">
                                            <img src="{{ asset('storage/' . $image->image_path) }}" alt="Image" width="100" class="rounded border mb-2">
                                            <button
    class="btn btn-sm btn-outline-danger"
    onclick="event.preventDefault();
        if(confirm('Delete this image?')) {
            let form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('requests.image.delete', $image->id) }}';

            let method = document.createElement('input');
            method.type = 'hidden';
            method.name = '_method';
            method.value = 'DELETE';

            let token = document.createElement('input');
            token.type = 'hidden';
            token.name = '_token';
            token.value = '{{ csrf_token() }}';

            form.appendChild(method);
            form.appendChild(token);
            document.body.appendChild(form);
            form.submit();
        }">
    Delete
</button>

                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Upload Additional Images (optional)</label>
                            <input type="file" name="images[]" class="form-control" multiple>
                            <small class="text-muted">You may upload multiple images.</small>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 rounded-pill py-2">
                            💾 Update Request
                        </button>
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection
