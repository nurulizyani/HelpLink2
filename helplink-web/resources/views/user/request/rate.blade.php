@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-lg p-4">
        <h3 class="mb-3">⭐ Rate This Request</h3>

        <p><strong>Item:</strong> {{ $claim->request->item_name }}</p>
        <p><strong>Requested by:</strong> {{ $claim->request->user->name ?? 'N/A' }}</p>

        <form method="POST" action="{{ route('claims.request.submitRating', $claim->id) }}">
            @csrf

            <div class="mb-3">
                <label for="rating" class="form-label">Rating (1-5)</label>
                <input type="number" class="form-control" name="rating" id="rating" min="1" max="5" required>
            </div>

            <div class="mb-3">
                <label for="comment" class="form-label">Comment (optional)</label>
                <textarea class="form-control" name="comment" id="comment" rows="3"></textarea>
            </div>

            <button type="submit" class="btn btn-success">Submit Rating</button>
        </form>
    </div>
</div>
@endsection
