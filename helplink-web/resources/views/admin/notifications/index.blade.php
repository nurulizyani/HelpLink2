@extends('layouts.admin')

@section('title', 'Notifications')
@section('page-title', 'Notifications')

@section('content')
<div class="container py-4">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-primary mb-1">
                <i class="fas fa-bell me-2"></i> All Notifications
            </h2>
            <small class="text-muted">
                System, offer, and request activities
            </small>
        </div>

        <form action="{{ route('admin.notifications.readAll') }}" method="POST">
            @csrf
            <button class="btn btn-sm btn-outline-success rounded-pill px-3">
                <i class="fas fa-check-double me-1"></i> Mark All as Read
            </button>
        </form>
    </div>

    @include('includes.alert')

    {{-- TABLE --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="bg-light small text-muted">
                        <tr>
                            <th class="ps-4">#</th>
                            <th>Title</th>
                            <th>Message</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th class="text-center pe-4">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($notifications as $index => $notif)
                        <tr>
                            <td class="ps-4">{{ $index + 1 }}</td>

                            <td class="fw-semibold">{{ $notif->title }}</td>

                            <td>{{ $notif->message }}</td>

                            <td>
                                <span class="badge rounded-pill bg-info-subtle text-info px-3">
                                    {{ ucfirst($notif->type) }}
                                </span>
                            </td>

                            <td>
                                @if(!$notif->is_read)
                                    <span class="badge bg-warning text-dark">Unread</span>
                                @else
                                    <span class="badge bg-success">Read</span>
                                @endif
                            </td>

                            <td class="text-center pe-4">
                                @if(!$notif->is_read)
                                <form action="{{ route('admin.notifications.read', $notif->id) }}"
                                      method="POST">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        Mark as Read
                                    </button>
                                </form>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                No notifications found
                            </td>
                        </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>

        </div>
    </div>

</div>
@endsection
