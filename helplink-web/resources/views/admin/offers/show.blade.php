@extends('layouts.admin')

@section('title', 'Offer Details')
@section('page-title', 'Offer Details')

@php use App\Helpers\ImageHelper; @endphp

@section('content')
<div class="container py-4">

    {{-- ================= PAGE HEADER ================= --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h2 class="fw-bold text-primary mb-1">
                <i class="fas fa-box-open me-2"></i> Offer Details
            </h2>
            <small class="text-muted">
                Review complete offer information submitted by the donor
            </small>
        </div>

        <a href="{{ route('admin.offers.index') }}"
           class="btn btn-light border rounded-pill px-4">
            ← Back
        </a>
    </div>

    {{-- ================= OFFER DETAILS CARD ================= --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body">
            <div class="row g-4 align-items-start">

                {{-- ================= OFFER IMAGE ================= --}}
                <div class="col-md-4">
                    <div class="border rounded-3 p-3 bg-light text-center">
                        @if($offer->image)
                            <img src="{{ ImageHelper::url($offer->image) }}"
                                 alt="Offer Image"
                                 class="img-fluid rounded"
                                 style="max-height:360px;object-fit:contain;">
                        @else
                            <div class="text-muted small">
                                <i class="fas fa-image me-1"></i>
                                No image uploaded
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ================= OFFER INFO ================= --}}
                <div class="col-md-8">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h4 class="fw-bold mb-1">{{ $offer->item_name }}</h4>
                            <small class="text-muted">
                                Submitted by {{ $offer->user->name ?? '-' }}
                                ({{ $offer->user->email ?? '-' }})
                            </small>
                        </div>

                    {{-- STATUS BADGE --}}
                    @switch($offer->status)

                        @case('available')
                            <span class="badge rounded-pill bg-success-subtle text-success px-3 py-2">
                                Available
                            </span>
                            @break

                        @case('claimed')
                            <span class="badge rounded-pill bg-info-subtle text-info px-3 py-2">
                                Claimed
                            </span>
                            @break

                        @case('completed')
                            <span class="badge rounded-pill bg-secondary-subtle text-secondary px-3 py-2">
                                Completed
                            </span>
                            @break

                        @default
                            <span class="badge rounded-pill bg-light text-muted px-3 py-2">
                                {{ ucfirst($offer->status) }}
                            </span>
                    @endswitch
                </div>

                    <hr>

                    <div class="row small mb-3 align-items-center">

                        <div class="col-md-6">
                            <div class="text-muted fw-semibold mb-1">Category</div>
                            <span class="badge bg-light text-dark border px-3 py-1">
                                {{ $offer->category ?? '-' }}
                            </span>
                        </div>

                        <div class="col-md-6">
                            <div class="text-muted fw-semibold mb-1">Quantity</div>
                            <div>{{ $offer->quantity ?? 1 }}</div>
                        </div>

                    </div>

                    <div class="row small mb-3 align-items-center">

                        <div class="col-md-6">
                            <div class="text-muted fw-semibold mb-1">Delivery Type</div>
                            <div>{{ ucfirst($offer->delivery_type) }}</div>
                        </div>

                        <div class="col-md-6">
                            <div class="text-muted fw-semibold mb-1">Posted At</div>
                            <div>{{ $offer->created_at?->format('d M Y, h:i A') ?? '-' }}</div>
                        </div>

                    </div>

                    {{-- DESCRIPTION --}}
                    <div class="mb-3">
                        <div class="text-muted fw-semibold mb-1">Description</div>
                        <div class="small text-muted" style="line-height:1.6;">
                            {{ $offer->description ?? '-' }}
                        </div>
                    </div>

                    {{-- ADDRESS --}}
                    <div>
                        <div class="text-muted fw-semibold mb-1">Address</div>
                        <div class="small">
                            <i class="fas fa-map-marker-alt text-muted me-1"></i>
                            {{ $offer->address ?? '-' }}
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- ================= ADMIN NOTE ================= --}}
    <div class="alert alert-light border small mb-0">
        <i class="fas fa-info-circle me-1"></i>
        <strong>Note:</strong>
        This page is for viewing offer details only. Offer status changes and claims
        are managed through the respective workflows.
    </div>

</div>
@endsection
