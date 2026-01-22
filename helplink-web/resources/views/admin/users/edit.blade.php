@extends('layouts.admin')

@section('title', 'Edit User')
@section('page-title', 'Edit User')

@section('content')
<div class="container py-4">

    {{-- PAGE HEADER --}}
    <div class="mb-4">
        <h2 class="fw-bold text-primary mb-1">
            <i class="fas fa-user-pen me-2"></i> Edit User
        </h2>
        <small class="text-muted">
            Update user information and contact details
        </small>
    </div>

    @include('includes.alert')

    {{-- USER SUMMARY --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h6 class="fw-bold mb-1">{{ $user->name }}</h6>
                <small class="text-muted">{{ $user->email }}</small>
            </div>

            <div>
                @if($user->email_verified_at)
                    <span class="badge rounded-pill bg-success-subtle text-success px-3 py-2">
                        <i class="fas fa-circle-check me-1"></i> Verified
                    </span>
                @else
                    <span class="badge rounded-pill bg-warning-subtle text-warning px-3 py-2">
                        <i class="fas fa-circle-exclamation me-1"></i> Unverified
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- EDIT FORM --}}
    <form id="editUserForm"
          action="{{ route('admin.users.update', $user->id) }}"
          method="POST"
          class="card border-0 shadow-sm rounded-4 p-4 bg-white">

        @csrf
        @method('PUT')

        <div class="row g-4">

            {{-- BASIC INFO --}}
            <div class="col-md-6">
                <h6 class="fw-bold mb-3 text-secondary">
                    <i class="fas fa-id-card me-2"></i> Basic Information
                </h6>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Full Name</label>
                    <input type="text"
                           name="name"
                           value="{{ old('name', $user->name) }}"
                           class="form-control rounded-3"
                           required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Address</label>
                    <input type="email"
                           name="email"
                           value="{{ old('email', $user->email) }}"
                           class="form-control rounded-3"
                           required>
                </div>
            </div>

            {{-- CONTACT INFO --}}
            <div class="col-md-6">
                <h6 class="fw-bold mb-3 text-secondary">
                    <i class="fas fa-phone me-2"></i> Contact Information
                </h6>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Phone Number</label>
                    <input type="text"
                           name="phone_number"
                           value="{{ old('phone_number', $user->phone_number) }}"
                           class="form-control rounded-3"
                           placeholder="e.g. 0123456789">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Address</label>
                    <textarea name="address"
                              rows="3"
                              class="form-control rounded-3"
                              placeholder="User address">{{ old('address', $user->address) }}</textarea>
                </div>
            </div>

        </div>

        {{-- ACTIONS --}}
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4 pt-3 border-top">

            <a href="{{ route('admin.users.index') }}"
               class="btn btn-light border px-4 rounded-pill">
                ← Cancel
            </a>

            {{-- OPEN SAVE MODAL --}}
            <button type="button"
                    class="btn btn-success px-4 rounded-pill"
                    data-bs-toggle="modal"
                    data-bs-target="#saveUserModal">
                <i class="fas fa-save me-1"></i> Save Changes
            </button>

        </div>
    </form>

</div>

{{-- ================= SAVE CONFIRM MODAL (STANDARD REQUEST STYLE) ================= --}}
<div class="modal fade" id="saveUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title text-success">
                    Save Changes
                </h5>
                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                Save changes for <strong>{{ $user->name }}</strong>?
                <div class="text-muted small mt-2">
                    User information will be updated.
                </div>
            </div>

            <div class="modal-footer">
                <button type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal">
                    Cancel
                </button>

                <button type="button"
                        class="btn btn-success"
                        onclick="document.getElementById('editUserForm').submit();">
                    Yes, Save
                </button>
            </div>
        </div>
    </div>
</div>

@endsection
