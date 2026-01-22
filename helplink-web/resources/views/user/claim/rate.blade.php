@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h3 class="mb-4"><i class="fas fa-star me-2 text-warning"></i>Rate Offer</h3>

    {{-- Offer Info --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="mb-1 fw-bold">{{ $claim->offer->item_name }}</h5>
            <p class="mb-0 text-muted">{{ $claim->offer->description }}</p>
        </div>
    </div>

    {{-- Rating Form --}}
    <form action="{{ route('claims.submitRating', $claim->id) }}" method="POST" class="mb-3">
        @csrf
        <div class="mb-3">
            <label for="rating" class="form-label">Rating (1–5)</label>
            <select name="rating" id="rating" class="form-select" required>
                <option value="" disabled selected>Choose...</option>
                @for ($i = 1; $i <= 5; $i++)
                    <option value="{{ $i }}">{{ $i }}</option>
                @endfor
            </select>
        </div>

        <div class="mb-3">
            <label for="comment" class="form-label">Comment (optional)</label>
            <textarea name="comment" id="comment" class="form-control" rows="2" placeholder="Write your feedback..."></textarea>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-success">✅ Submit Rating</button>
            <a href="{{ route('claims.offer.my') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>

    {{-- Report Button (separate GET form) --}}
    <form action="{{ route('reports.create', ['id' => $claim->offer->user_id]) }}" method="GET">
    <button type="submit" class="btn btn-warning">🚩 Report User</button>
</form>

</div>
@endsection
