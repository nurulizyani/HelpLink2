@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h4 class="fw-bold mb-4">💬 Your Conversations</h4>

    <div class="list-group shadow-sm rounded-3">
        @forelse($conversations as $userId => $messages)
    @php
        $lastMessage = $messages->last();
        $user = \App\Models\User::find($userId);
        $isUnread = $unreadFlags[$userId] ?? false;
    @endphp

    <a href="{{ route('chat.show', $user->id) }}" 
       class="list-group-item list-group-item-action d-flex align-items-center gap-3 
              {{ $isUnread ? 'fw-bold bg-light' : '' }}">
        {{-- Avatar --}}
        <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center" style="width: 40px; height: 40px; font-weight: bold;">
            {{ strtoupper(substr($user->name, 0, 1)) }}
        </div>

        {{-- Details --}}
        <div>
            <div>{{ $user->name }}</div>
            <div class="text-muted small">
                {{ Str::limit($lastMessage->message, 50) }} · {{ $lastMessage->created_at->diffForHumans() }}
            </div>
        </div>

        {{-- New dot on right --}}
        @if($isUnread)
            <span class="ms-auto text-success">●</span>
        @endif
    </a>
@empty
    <div class="text-muted p-3 text-center">
        You have no conversations yet.
    </div>
@endforelse

    </div>
</div>
@endsection
