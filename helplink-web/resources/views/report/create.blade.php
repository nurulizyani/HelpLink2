@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-4">Report {{ $user->name }}</h3>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>There were some problems:</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

        <form action="{{ route('reports.store', $user->id) }}" method="POST" enctype="multipart/form-data">
        @csrf

        {{-- Hidden input to redirect back to rating page if report from there --}}
        @if(request()->has('claim_id'))
            <input type="hidden" name="claim_id" value="{{ request('claim_id') }}">
        @endif

        <div class="mb-3">
            <label for="reason" class="form-label">Reason for reporting:</label>
            <textarea name="reason" id="reason" class="form-control" rows="4" required>{{ old('reason') }}</textarea>
        </div>

        <div class="mb-3">
            <label for="evidence" class="form-label">Upload Evidence (optional)</label>
            <input type="file" name="evidence" id="evidence" class="form-control" accept="image/*">
        </div>

        <button type="submit" class="btn btn-danger">Submit Report</button>
        <a href="{{ url()->previous() }}" class="btn btn-secondary">Cancel</a>
    </form>

</div>
@endsection
