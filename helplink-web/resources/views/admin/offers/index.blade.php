@extends('layouts.admin')

@section('title', 'All Offers')
@section('page-title', 'All Offers')

@section('content')
<div class="container-fluid py-4">

    {{-- ================= PAGE HEADER ================= --}}
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
        <h2 class="fw-bold text-primary mb-1">
            <i class="fas fa-gift me-2"></i> All Offers
        </h2>
        <small class="text-muted">
            Monitor and manage items offered by users
        </small>
    </div>

    <div class="d-flex gap-2 align-items-center">
        <span class="badge bg-primary fs-6 px-3 py-2">
            {{ $offers->count() }} Offers
        </span>

        <a href="{{ route('admin.offers.export') }}"
           class="btn btn-sm btn-outline-success rounded-pill px-3">
            <i class="fas fa-file-csv me-1"></i> Export CSV
        </a>
    </div>
</div>


    @include('includes.alert')

    {{-- ================= INFO NOTE ================= --}}
    <div class="alert alert-light border small mb-3">
        <i class="fas fa-info-circle me-1"></i>
        <strong>Note:</strong>
        This list is for monitoring purposes. Abnormal offers may be flagged
        for further review before removal.
    </div>

    {{-- ================= STATUS FILTER ================= --}}
    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body py-3">
            <div class="d-flex gap-2 flex-wrap">

                <button class="btn btn-sm btn-outline-primary rounded-pill px-3 filter-btn active"
                        data-status="all">All</button>

                <button class="btn btn-sm btn-outline-success rounded-pill px-3 filter-btn"
                        data-status="available">Available</button>

                <button class="btn btn-sm btn-outline-info rounded-pill px-3 filter-btn"
                        data-status="claimed">Claimed</button>

                <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 filter-btn"
                        data-status="completed">Completed</button>

                <button class="btn btn-sm btn-outline-danger rounded-pill px-3 filter-btn"
                        data-status="flagged">Flagged</button>

            </div>
        </div>
    </div>

    {{-- ================= OFFERS TABLE ================= --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="bg-light small text-muted">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Item</th>
                            <th>Donor</th>
                            <th>Qty</th>
                            <th>Status</th>
                            <th>Posted At</th>
                            <th class="text-center pe-4">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse($offers as $offer)
                        <tr class="offer-row"
                            data-status="{{ strtolower($offer->status) }}">

                            <td class="ps-4 fw-semibold text-muted">
                                #{{ $offer->offer_id }}
                            </td>

                            <td class="fw-semibold">
                                {{ $offer->item_name }}
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{ $offer->user->name ?? 'Unknown' }}
                                </div>
                                <div class="text-muted small">
                                    {{ $offer->user->email ?? '-' }}
                                </div>
                            </td>

                            <td>{{ $offer->quantity }}</td>

                            <td>
                                @switch(strtolower($offer->status))

                                    @case('available')
                                        <span class="badge rounded-pill bg-success-subtle text-success px-3">
                                            Available
                                        </span>
                                        @break

                                    @case('claimed')
                                        <span class="badge rounded-pill bg-info-subtle text-info px-3">
                                            Claimed
                                        </span>
                                        @break

                                    @case('completed')
                                        <span class="badge rounded-pill bg-secondary-subtle text-secondary px-3">
                                            Completed
                                        </span>
                                        @break

                                    @case('flagged')
                                        <span class="badge rounded-pill bg-danger-subtle text-danger px-3">
                                            Flagged
                                        </span>
                                        @break

                                    @default
                                        <span class="badge rounded-pill bg-light text-muted px-3">
                                            {{ ucfirst($offer->status) }}
                                        </span>
                                @endswitch
                            </td>

                            <td>
                                {{ $offer->created_at?->format('d M Y, h:i A') ?? '-' }}
                            </td>

                            <td class="text-center pe-4">
                                <div class="d-flex justify-content-center gap-1 flex-wrap">

                                    {{-- VIEW --}}
                                    <a href="{{ route('admin.offers.show', $offer->offer_id) }}"
                                       class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                                        View
                                    </a>

                                    {{-- FLAG / UNFLAG --}}
                                    @if($offer->status !== 'flagged')
                                        <form method="POST"
                                              action="{{ route('admin.offers.flag', $offer->offer_id) }}">
                                            @csrf
                                            @method('PUT')
                                            <button class="btn btn-sm btn-outline-warning rounded-pill px-3">
                                                Flag
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST"
                                              action="{{ route('admin.offers.unflag', $offer->offer_id) }}">
                                            @csrf
                                            @method('PUT')
                                            <button class="btn btn-sm btn-outline-success rounded-pill px-3">
                                                Unflag
                                            </button>
                                        </form>
                                    @endif

                                    {{-- DELETE --}}
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteOfferModal"
                                            data-offer-id="{{ $offer->offer_id }}"
                                            data-offer-name="{{ $offer->item_name }}">
                                        Delete
                                    </button>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                No offers found
                            </td>
                        </tr>
                    @endforelse
                    </tbody>

                </table>
            </div>
        </div>
    </div>
</div>

{{-- ================= DELETE MODAL ================= --}}
<div class="modal fade" id="deleteOfferModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form method="POST" id="deleteOfferForm">
                @csrf
                @method('DELETE')

                <div class="modal-header">
                    <h5 class="modal-title text-danger">Delete Offer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    Delete <strong id="deleteOfferName"></strong>?
                    <div class="text-danger small mt-2">
                        This action cannot be undone.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        Yes, Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ================= FILTER + DELETE SCRIPT ================= --}}
<script>
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.filter-btn')
            .forEach(b => b.classList.remove('active'));

        this.classList.add('active');

        const status = this.dataset.status;
        document.querySelectorAll('.offer-row').forEach(row => {
            row.style.display =
                status === 'all' || row.dataset.status === status ? '' : 'none';
        });
    });
});

document.getElementById('deleteOfferModal')
    .addEventListener('show.bs.modal', function (event) {

    const button = event.relatedTarget;

    document.getElementById('deleteOfferName').innerText =
        button.getAttribute('data-offer-name');

    document.getElementById('deleteOfferForm').action =
        `/admin/offers/${button.getAttribute('data-offer-id')}`;
});
</script>
@endsection

