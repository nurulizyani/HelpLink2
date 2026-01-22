@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-4">Rate Fulfilled Request</h3>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h5>{{ $claim->request->item_name }}</h5>
            <p>{{ $claim->request->description ?? 'No description' }}</p>
        </div>
    </div>

    <form action="{{ route('claims.request.submitRating', $claim->id) }}" method="POST">
        @csrf

        <div class="mb-3">
            <label for="rating" class="form-label">Rating (1-5)</label>
            <select name="rating" id="rating" class="form-select" required>
                <option value="">Choose...</option>
                @for($i = 1; $i <= 5; $i++)
                    <option value="{{ $i }}">{{ $i }} Star</option>
                @endfor
            </select>
        </div>

        <div class="mb-3">
            <label for="comment" class="form-label">Comment (optional)</label>
            <textarea name="comment" class="form-control" rows="3"></textarea>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success">Submit Rating</button>
            <a href="{{ route('claims.request.my') }}" class="btn btn-secondary">Cancel</a>
            <a href="{{ route('reports.create', ['id' => $claim->request->user_id]) }}" class="btn btn-warning">Report User</a>
        </div>
    </form>
</div>
@endsection
