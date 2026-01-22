@extends('layouts.admin')

@section('title', 'User Reports')

@section('content')
<div class="container py-4">
    <h2 class="fw-bold text-primary mb-4">🚩 User Reports</h2>

    @include('includes.alert')

    <div class="table-responsive shadow-sm rounded-4">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Reported User</th>
                    <th>Reporter</th>
                    <th>Reason</th>
                    <th>Evidence</th>
                    <th>Reported At</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reports as $report)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $report->reportedUser->name ?? '-' }}</td>
                    <td>{{ $report->reporter->name ?? '-' }}</td>
                    <td>{{ $report->reason }}</td>
                    <td>
                        @if ($report->image)
                            <a href="{{ asset('storage/' . $report->image) }}" target="_blank">View Image</a>
                        @else
                            <span class="text-muted">No Image</span>
                        @endif

                    </td>
                    <td>{{ \Carbon\Carbon::parse($report->created_at)->format('d M Y, h:i A') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">No reports found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
