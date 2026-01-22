@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-header bg-white border-0">
            <a href="{{ route('chat.inbox') }}" class="btn btn-sm btn-light border rounded-pill mb-2">
                ← Back to Messages
            </a>
            <h5 class="fw-bold mb-0">
                💬 Chat with {{ $receiver->name }}
            </h5>
            @if(isset($requestId))
                <div class="small text-muted">Request ID: {{ $requestId }}</div>
            @endif
        </div>

        <div class="card-body px-4 py-3" style="height: 450px; overflow-y: scroll;" id="chat-box">
            @forelse($messages as $msg)
                <div class="d-flex mb-3 {{ $msg->sender_id === Auth::id() ? 'justify-content-end' : 'justify-content-start' }}">
                    <div class="p-3 rounded-4 shadow-sm 
                        {{ $msg->sender_id === Auth::id() ? 'bg-primary text-white' : 'bg-light' }}" 
                        style="max-width: 70%;">
                        <div class="fw-normal">{{ $msg->message }}</div>
                        <div class="text-end small mt-1 
                            {{ $msg->sender_id === Auth::id() ? 'text-white-50' : 'text-muted' }}">
                            {{ $msg->created_at->diffForHumans() }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-muted text-center mt-5">No messages yet. Start chatting now!</div>
            @endforelse
        </div>

        <div class="card-footer bg-white border-0">
            <form action="{{ isset($requestId) ? route('request.chat.send', $requestId) : route('chat.send') }}" method="POST" class="d-flex gap-2">
                @csrf
                <input type="hidden" name="receiver_id" value="{{ $receiver->id }}">
                @if(isset($requestId))
                    <input type="hidden" name="request_id" value="{{ $requestId }}">
                @endif
                <input type="text" name="message" class="form-control rounded-pill px-4" placeholder="Type your message..." required>
                <button class="btn btn-primary rounded-pill px-4">Send</button>
            </form>
        </div>
    </div>
</div>

{{-- Auto scroll to bottom --}}
<script>
    const chatBox = document.getElementById('chat-box');
    chatBox.scrollTop = chatBox.scrollHeight;
</script>

@endsection
