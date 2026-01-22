@extends('layouts.admin')

@section('title', 'Request Management')
@section('page-title', 'Request Management')

@section('content')
<div class="container-fluid py-4">

    {{-- ================= PAGE HEADER ================= --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h2 class="fw-bold text-primary mb-1">
                <i class="fas fa-hand-holding-heart me-2"></i> Request Management
            </h2>
            <small class="text-muted">
                Review and prioritise help requests before making approval decisions
            </small>
        </div>

        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-primary fs-6 px-3 py-2">
                {{ $requests->count() }} Requests
            </span>

            <a href="{{ route('admin.requests.export') }}"
               class="btn btn-sm btn-outline-success rounded-pill px-3">
                <i class="fas fa-file-csv me-1"></i> Export CSV
            </a>
        </div>
    </div>

    @include('includes.alert')

    {{-- ================= SCREENING NOTE ================= --}}
    <div class="alert alert-light border small mb-3">
        <i class="fas fa-info-circle me-1"></i>
        <strong>Note:</strong>
        This list is used for initial screening only.
        Approval and rejection actions are performed on the request details page
        after full document review.
    </div>

    {{-- ================= FILTER ================= --}}
    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body py-3">
            <div class="d-flex gap-2 flex-wrap align-items-center">

                <button class="btn btn-sm btn-outline-primary rounded-pill px-3 filter-btn active" data-status="all">
                    All
                </button>
                <button class="btn btn-sm btn-outline-warning rounded-pill px-3 filter-btn" data-status="pending">
                    Pending
                </button>
                <button class="btn btn-sm btn-outline-success rounded-pill px-3 filter-btn" data-status="approved">
                    Approved
                </button>
                <button class="btn btn-sm btn-outline-danger rounded-pill px-3 filter-btn" data-status="rejected">
                    Rejected
                </button>

                <div class="ms-auto">
                    <select id="confidenceFilter"
                            class="form-select form-select-sm rounded-pill px-3"
                            style="min-width:220px;">
                        <option value="all">AI Document Confidence</option>
                        <option value="high">High (≥ 80%)</option>
                        <option value="medium">Medium (60–79%)</option>
                        <option value="low">Low (&lt; 60%)</option>
                    </select>
                </div>

            </div>
        </div>
    </div>

    {{-- ================= TABLE ================= --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="bg-light small text-muted">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>User</th>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>AI Review</th>
                            <th class="text-center pe-4">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse($requests as $req)
                        <tr class="request-row"
                            data-status="{{ $req->status }}"
                            data-confidence="{{ $req->ai_confidence ?? 0 }}">

                            <td class="ps-4 fw-semibold text-muted">#{{ $req->id }}</td>

                            <td>
                                <div class="fw-semibold">{{ $req->user->name ?? 'Unknown' }}</div>
                                <div class="text-muted small">{{ $req->user->email ?? '-' }}</div>
                            </td>

                            <td class="fw-semibold">{{ $req->item_name }}</td>

                            <td>
                                <span class="badge bg-light border text-dark px-3">
                                    {{ $req->category }}
                                </span>
                            </td>

                            <td>
                                @switch($req->status)
                                    @case('approved')
                                        <span class="badge rounded-pill bg-success-subtle text-success px-3">
                                            Approved
                                        </span>
                                        @break
                                    @case('rejected')
                                        <span class="badge rounded-pill bg-danger-subtle text-danger px-3">
                                            Rejected
                                        </span>
                                        @break
                                    @default
                                        <span class="badge rounded-pill bg-warning-subtle text-warning px-3">
                                            Pending
                                        </span>
                                @endswitch
                            </td>

                            <td>
                                @if($req->ai_summary)
                                    <span
                                        class="badge 
                                            {{ ($req->ai_confidence ?? 0) >= 80 ? 'bg-success' : (($req->ai_confidence ?? 0) >= 60 ? 'bg-warning text-dark' : 'bg-danger') }}
                                            rounded-pill px-3 py-2"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="AI confidence reflects document clarity and extraction accuracy, not approval or rejection decision."
                                    >
                                        {{ $req->ai_confidence }}% Confidence
                                    </span>
                                @else
                                    <span
                                        class="badge bg-secondary rounded-pill px-3 py-2"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="No document was available for AI analysis."
                                    >
                                        AI Not Available
                                    </span>
                                @endif
                            </td>

                            <td class="text-center pe-4">
                                <a href="{{ route('admin.requests.show', $req->id) }}"
                                   class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                                    View Details
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                No requests found
                            </td>
                        </tr>
                    @endforelse
                    </tbody>

                </table>
            </div>
        </div>
    </div>
</div>

{{-- ================= FILTER SCRIPT ================= --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    const rows = document.querySelectorAll('.request-row');
    const statusButtons = document.querySelectorAll('.filter-btn');
    const confidenceSelect = document.getElementById('confidenceFilter');

    function applyFilter() {
        const activeStatus = document.querySelector('.filter-btn.active').dataset.status;
        const confidence = confidenceSelect.value;

        rows.forEach(row => {
            let show = true;
            const rowStatus = row.dataset.status;
            const rowConfidence = parseInt(row.dataset.confidence);

            if (activeStatus !== 'all' && rowStatus !== activeStatus) show = false;

            if (confidence !== 'all') {
                if (confidence === 'high' && rowConfidence < 80) show = false;
                if (confidence === 'medium' && (rowConfidence < 60 || rowConfidence >= 80)) show = false;
                if (confidence === 'low' && rowConfidence >= 60) show = false;
            }

            row.style.display = show ? '' : 'none';
        });
    }

    statusButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            statusButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            applyFilter();
        });
    });

    confidenceSelect.addEventListener('change', applyFilter);
});
</script>
@endsection
