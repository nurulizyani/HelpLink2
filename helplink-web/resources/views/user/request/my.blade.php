@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h3 class="mb-4 fw-bold text-primary">📦 My Requests</h3>

    @include('includes.alert')

    @if ($requests->isEmpty())
        <div class="alert alert-info">You have not made any requests yet.</div>
    @else
        @php
            $grouped = $requests->groupBy('status');
        @endphp

        @foreach (['pending', 'approved', 'claimed', 'completed', 'rejected'] as $status)
            @if ($grouped->has($status))
                <h5 class="fw-bold mt-4">
                    @if($status === 'approved')
                        ✅ Approved Requests
                    @elseif($status === 'pending')
                        ⏳ Pending Requests
                    @elseif($status === 'claimed')
                        📦 Claimed Requests (Waiting for Completion)
                    @elseif($status === 'completed')
                        🌟 Completed Requests
                    @elseif($status === 'rejected')
                        ❌ Rejected Requests
                    @endif
                </h5>

                <div class="row g-4">
                    @foreach ($grouped[$status] as $request)
                        @php
                            $isClaimed = $request->claims()->exists();
                            $borderClass = match($request->status) {
                                'approved' => 'border-start border-4 border-success',
                                'rejected' => 'border-start border-4 border-danger',
                                'claimed' => 'border-start border-4 border-secondary',
                                'completed' => 'border-start border-4 border-primary',
                                default => 'border-start border-4 border-warning',
                            };
                        @endphp

                        <div class="col-md-6">
                            <div class="card shadow-sm rounded-4 h-100 {{ $borderClass }}">
                                <div class="card-body">
                                    <h5 class="card-title fw-bold">{{ $request->item_name }}</h5>

                                    <p class="mb-1">
                                        <strong>Status:</strong>
                                        @if($request->status === 'approved')
                                            <span class="badge bg-success">Approved</span>
                                        @elseif($request->status === 'rejected')
                                            <span class="badge bg-danger">Rejected</span>
                                        @elseif($request->status === 'claimed')
                                            <span class="badge bg-secondary">Claimed</span>
                                        @elseif($request->status === 'completed')
                                            <span class="badge bg-primary">Completed</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @endif
                                    </p>

                                    <p class="mb-1"><strong>Quantity:</strong> {{ $request->quantity }}</p>
                                    <p class="mb-1"><strong>Delivery:</strong> {{ ucfirst($request->delivery_type) }}</p>
                                    <p class="mb-1"><strong>Location:</strong> {{ $request->location }}</p>
                                    <p class="mb-1"><strong>Claimed by:</strong> {{ $request->claims->first()->user->name ?? 'Unknown' }}</p>


                                    {{-- Notice Section --}}
                                    @if($request->status === 'pending' && $isClaimed)
                                        <p class="text-muted mt-2 small fst-italic">
                                            This request is pending but already claimed and cannot be edited or deleted.
                                        </p>
                                    @elseif($request->status !== 'pending')
                                        <p class="text-muted mt-2 small fst-italic">
                                            This request is {{ $request->status }} and cannot be edited or deleted.
                                        </p>
                                    @endif

                                    {{-- Action Buttons --}}
                                    <div class="mt-3 d-flex flex-wrap gap-2">
                                        <a href="{{ route('requests.show', $request->id) }}" class="btn btn-sm btn-outline-primary">
                                            View Details
                                        </a>

                                        @if($request->status === 'pending' && !$isClaimed)
                                            <a href="{{ route('requests.edit', $request->id) }}" class="btn btn-sm btn-outline-secondary">
                                                Edit
                                            </a>
                                            <form action="{{ route('requests.destroy', $request->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this request?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endforeach
    @endif
</div>
@endsection
