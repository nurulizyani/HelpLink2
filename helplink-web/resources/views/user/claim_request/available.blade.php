@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h3 class="mb-4">Available Requests to Fulfill</h3>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($requests->isEmpty())
        <p>No available requests at the moment.</p>
    @else
        <div class="row row-cols-1 row-cols-md-2 g-4">
            @foreach($requests as $request)
                <div class="col">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body d-flex flex-column justify-content-between">
                            <div>
                                <h5 class="card-title">{{ $request->item_name }}</h5>
                                <p class="card-text text-muted">{{ $request->description ?? '-' }}</p>

                                <p class="card-text mb-1">
                                    <strong>Delivery Type:</strong>
                                    <span class="badge bg-info text-dark">
                                        {{ ucfirst($request->delivery_type ?? '-') }}
                                    </span>
                                </p>

                                <p class="card-text">
                                    <strong>Address:</strong> {{ $request->address ?? '-' }}
                                </p>
                            </div>

                            <div class="mt-3 text-end">
                                <a href="{{ route('request.show', $request->id) }}" class="btn btn-outline-primary btn-sm">
                                    View More
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
