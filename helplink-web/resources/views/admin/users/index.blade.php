@extends('layouts.admin')

@section('title', 'Manage Users')
@section('page-title', 'Manage Users')

@section('content')
<div class="container py-4">

    {{-- ================= PAGE HEADER ================= --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h2 class="fw-bold text-primary mb-1">
                <i class="fas fa-users me-2"></i> Manage Users
            </h2>
            <small class="text-muted">
                View and manage registered users. Only verified users can access the mobile app.
            </small>
        </div>

        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-primary fs-6 px-3 py-2">
                {{ $users->count() }} Users
            </span>

            <a href="{{ route('admin.users.export') }}"
               class="btn btn-sm btn-outline-success rounded-pill px-3">
                <i class="fas fa-file-csv me-1"></i> Export CSV
            </a>
        </div>
    </div>

    @include('includes.alert')

    {{-- ================= SEARCH BAR ================= --}}
    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body py-3">
            <div class="row">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text"
                               id="userSearch"
                               class="form-control border-start-0"
                               placeholder="Search by name or email...">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= USERS TABLE ================= --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">

            <div class="table-responsive">
                <table class="table table-sm mb-0 users-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;"class="text-center">ID</th>
                            <th style="width: 30%;">Name & Email</th>
                            <th style="width: 100px;" class="text-center">Status</th>
                            <th style="width: 130px;"class="text-center">Joined</th>
                            <th style="width: 200px;" class="text-center">Actions</th>
                        </tr>
                    </thead>

                    <tbody id="usersTable">
                    @forelse($users as $user)
                        <tr class="user-row">

                            {{-- ID --}}
                            <td class="fw-semibold text-muted text-center">
                                #{{ $user->id }}
                            </td>

                            {{-- NAME + EMAIL --}}
                            <td>
                                <div class="fw-semibold user-name">
                                    {{ $user->name }}
                                </div>
                                <div class="text-muted small user-email">
                                    {{ $user->email }}
                                </div>
                            </td>

                            {{-- STATUS --}}
                            <td>
                                <div class="d-flex justify-content-center align-items-center">
                                    @if($user->email_verified_at)
                                        <span class="badge rounded-pill bg-success-subtle text-success px-3 user-status-badge">
                                            Verified
                                        </span>
                                    @else
                                        <span class="badge rounded-pill bg-warning-subtle text-warning px-3 user-status-badge">
                                            Unverified
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- JOINED --}}
                            <td class="text-center text-muted">
                                {{ $user->created_at->format('d M Y') }}
                            </td>

                            {{-- ACTIONS --}}
                            <td>
                                <div class="d-flex justify-content-center align-items-center gap-2">
                                    <a href="{{ route('admin.users.show', $user->id) }}"
                                       class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        View
                                    </a>

                                    <a href="{{ route('admin.users.edit', $user->id) }}"
                                       class="btn btn-sm btn-outline-warning rounded-pill px-3">
                                        Edit
                                    </a>

                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteUserModal"
                                            data-user-id="{{ $user->id }}"
                                            data-user-name="{{ $user->name }}">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                No users found.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>

                </table>
            </div>

        </div>
    </div>
</div>

{{-- ================= DELETE USER MODAL ================= --}}
<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form method="POST" id="deleteUserForm">
                @csrf
                @method('DELETE')

                <div class="modal-header">
                    <h5 class="modal-title text-danger">Delete User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    Delete <strong id="deleteUserName"></strong>?
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

{{-- ================= STYLE FIX (IMPORTANT) ================= --}}
<style>
.users-table {
    table-layout: fixed;
}

.users-table th,
.users-table td {
    vertical-align: middle !important;
}

.user-name,
.user-email {
    line-height: 1.2;
}

.user-status-badge {
    font-size: 0.75rem;
    padding-top: 0.25rem !important;
    padding-bottom: 0.25rem !important;
}
</style>

{{-- ================= SEARCH + DELETE SCRIPT ================= --}}
<script>
document.getElementById('userSearch').addEventListener('keyup', function () {
    const keyword = this.value.toLowerCase();
    document.querySelectorAll('.user-row').forEach(row => {
        const name = row.querySelector('.user-name').innerText.toLowerCase();
        const email = row.querySelector('.user-email').innerText.toLowerCase();
        row.style.display =
            name.includes(keyword) || email.includes(keyword) ? '' : 'none';
    });
});

const deleteUserModal = document.getElementById('deleteUserModal');
deleteUserModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const userId = button.getAttribute('data-user-id');
    const userName = button.getAttribute('data-user-name');

    document.getElementById('deleteUserName').innerText = userName;
    document.getElementById('deleteUserForm').action =
        `/admin/users/${userId}`;
});
</script>
@endsection
