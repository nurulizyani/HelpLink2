@extends('layouts.admin')

@section('title', 'Request Details')
@section('page-title', 'Request Details')

@section('content')
@php
    use App\Helpers\ImageHelper;
@endphp

<div class="container py-4">

    {{-- ================= PAGE HEADER ================= --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h2 class="fw-bold text-primary mb-1">
                <i class="fas fa-file-alt me-2"></i> Request Details
            </h2>
            <small class="text-muted">
                Review complete request information before taking action
            </small>
        </div>

        <a href="{{ route('admin.requests.index') }}"
           class="btn btn-light border rounded-pill px-4">
            ← Back
        </a>
    </div>

    {{-- ================= MAIN REQUEST CARD (LIKE OFFER UI) ================= --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body">
            <div class="row g-4 align-items-start">

                {{-- LEFT: ITEM IMAGE --}}
                <div class="col-md-4">
                    @if($request->image)
                        <img src="{{ ImageHelper::url($request->image) }}"
                             class="img-fluid rounded border"
                             style="max-height:420px;object-fit:contain;">
                        <a href="{{ ImageHelper::url($request->image) }}"
                        target="_blank"
                        class="text-primary small d-inline-block mt-2">
                            <i class="fas fa-expand me-1"></i> View full image
                        </a>
                    @else
                        <div class="alert alert-warning small mb-0">
                            No item image uploaded.
                        </div>
                    @endif
                </div>

                {{-- RIGHT: REQUEST INFO --}}
                <div class="col-md-8">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h4 class="fw-bold mb-1">{{ $request->item_name }}</h4>
                            <small class="text-muted">
                                Submitted by {{ $request->user->name ?? '-' }}
                                ({{ $request->user->email ?? '-' }})
                            </small>
                        </div>

                        <div>
                            @switch($request->status)
                                @case('approved')
                                    <span class="badge rounded-pill bg-success-subtle text-success px-3 py-2">
                                        Approved
                                    </span>
                                    @break
                                @case('rejected')
                                    <span class="badge rounded-pill bg-danger-subtle text-danger px-3 py-2">
                                        Rejected
                                    </span>
                                    @break
                                @case('fulfilled')
                                    <span class="badge rounded-pill bg-primary-subtle text-primary px-3 py-2">
                                        Fulfilled
                                    </span>
                                    @break
                                @default
                                    <span class="badge rounded-pill bg-warning-subtle text-warning px-3 py-2">
                                        Pending
                                    </span>
                            @endswitch
                        </div>
                    </div>

                    <hr class="my-3">

                    {{-- META INFO --}}
                    <div class="row small mb-3 align-items-center">
                        <div class="col-md-6">
                            <div class="text-muted fw-semibold mb-1">Category</div>
                            <span class="badge bg-light text-dark border px-3 py-1">
                                {{ $request->category }}
                            </span>
                        </div>

                        <div class="col-md-6">
                            <div class="text-muted fw-semibold mb-1">Submitted At</div>
                            <div>{{ $request->created_at?->format('d M Y, h:i A') ?? '-' }}</div>
                        </div>
                    </div>

                    {{-- DESCRIPTION --}}
                    <div class="mb-3">
                        <div class="text-muted fw-semibold mb-1">Description</div>
                        <div class="small text-muted" style="line-height:1.6;">
                            {{ $request->description }}
                        </div>
                    </div>

                    {{-- ADDRESS --}}
                    <div>
                        <div class="text-muted fw-semibold mb-1">Address</div>
                        <div class="small">
                            <i class="fas fa-map-marker-alt text-muted me-1"></i>
                            {{ $request->address }}
                        </div>
                    </div>
                </div>


            </div>
        </div>
    </div>

    {{-- ================= SUPPORTING DOCUMENT ================= --}}
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body">
        <h6 class="fw-bold mb-3 text-secondary">
            <i class="fas fa-file-alt me-2"></i> Supporting Document
        </h6>

        @if($request->document)
            <div class="d-flex align-items-start gap-3">
                {{-- Thumbnail --}}
                <img src="{{ ImageHelper::url($request->document) }}"
                     class="rounded border"
                     style="width:120px;height:160px;object-fit:cover;">

                {{-- Info --}}
                <div>
                    <div class="fw-semibold mb-1">
                        Document Uploaded
                    </div>

                    <div class="text-muted small mb-2">
                        Used for verification & AI analysis
                    </div>

                    <a href="{{ ImageHelper::url($request->document) }}"
                       target="_blank"
                       class="btn btn-outline-primary btn-sm">
                        View Full Document
                    </a>
                </div>
            </div>
        @else
            <div class="alert alert-warning small mb-0">
                No supporting document was uploaded for this request.
            </div>
        @endif
    </div>
</div>

    {{-- ================= AI ANALYSIS ================= --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-4 border-primary">
        <div class="card-body">
            <h6 class="fw-bold mb-3 text-secondary">
                <i class="fas fa-robot me-2"></i> AI-Assisted Review
            </h6>

            @php
                $confidence = $request->ai_confidence ?? 0;
                $priority = 'normal';

                if ($request->ai_extracted_data) {
                    $data = json_decode($request->ai_extracted_data, true);
                    if (isset($data['net_salary']) && $data['net_salary'] < 2000) {
                        $priority = 'high';
                    }
                }
            @endphp

            <div class="row g-3 small">
                <div class="col-md-4">
                    <strong>Document Type</strong><br>
                    {{ $request->ai_document_type ?? 'Not detected' }}
                </div>

                <div class="col-md-4">
    <strong>
        Document Confidence
        <i class="fas fa-info-circle text-muted ms-1"
           data-bs-toggle="tooltip"
           data-bs-placement="top"
           title="Confidence score generated by AI based on document clarity, readability and data consistency. This score is for reference only and does not determine approval decisions.">
        </i>
    </strong>
    <br>

    <span class="badge 
        {{ $confidence >= 80 ? 'bg-success' : ($confidence >= 50 ? 'bg-warning text-dark' : 'bg-danger') }}
        px-3 py-2">
        {{ $confidence }}% Reliable
    </span>
</div>

            
                <div class="col-md-4">
                <strong>
                    Review Priority
                    <i class="fas fa-info-circle text-muted ms-1"
                    data-bs-toggle="tooltip"
                    title="AI-generated review urgency to assist administrators. Final approval decisions are made by administrators.">
                    </i>
                </strong><br>

                <span class="badge {{ $priority === 'high' ? 'bg-danger' : 'bg-secondary' }} px-3 py-2">
                    {{ ucfirst($priority) }}
                </span>
            </div>


                <div class="col-12 mt-2">
                    <strong>AI Summary</strong><br>
                    {{ $request->ai_summary ?? 'AI analysis not available.' }}
                </div>
            </div>

            @if($request->ai_extracted_data && is_array(json_decode($request->ai_extracted_data, true)))
                @php $data = json_decode($request->ai_extracted_data, true); @endphp
                <hr>
                <strong>Extracted Key Information</strong>
                <div class="row g-2 mt-2 small">
                    @foreach($data as $key => $value)
                        <div class="col-md-6">
                            <div class="border rounded p-2 bg-white">
                                <div class="fw-semibold text-muted">
                                    {{ ucwords(str_replace('_',' ',$key)) }}
                                </div>
                                <div>{{ $value }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="alert alert-warning mt-3 small mb-0">
                <strong>Note:</strong> AI analysis is provided as decision support only.
                Final approval decisions are made by administrators.
            </div>
        </div>
    </div>

    {{-- ================= ADMIN ACTIONS ================= --}}
    @if($request->status === 'pending')
        <div class="d-flex justify-content-end gap-2">
            <button class="btn btn-success rounded-pill px-4"
                    data-bs-toggle="modal"
                    data-bs-target="#approveModal">
                Approve
            </button>

            <button class="btn btn-danger rounded-pill px-4"
                    data-bs-toggle="modal"
                    data-bs-target="#rejectModal">
                Reject
            </button>
        </div>
    @endif
</div>

{{-- ================= APPROVE MODAL ================= --}}
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">
            <div class="modal-header">
                <h5 class="modal-title">Approve Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST" action="{{ route('admin.requests.updateStatus', $request->id) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="status" value="approved">

                <div class="modal-body">
                    Are you sure you want to approve this request?
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Confirm Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ================= REJECT MODAL ================= --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">
            <div class="modal-header">
                <h5 class="modal-title">Reject Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST" action="{{ route('admin.requests.updateStatus', $request->id) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="status" value="rejected">

                <div class="modal-body">
                    <label class="form-label fw-semibold">
                        Admin Remark <span class="text-muted">(Optional)</span>
                    </label>
                    <textarea name="admin_remark"
                              class="form-control"
                              rows="3"
                              placeholder="Optional reason or internal note"></textarea>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
