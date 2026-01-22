@extends('layouts.app')

@section('content')
<div class="container py-5 text-center">
    <h2 class="mb-4">Email Verification Required</h2>
    <p class="mb-4">Before accessing the dashboard, please verify your email address.</p>

    @if (session('resent'))
        <div class="alert alert-success rounded-pill">
            A new verification link has been sent to your email address.
        </div>
    @endif

    <form class="d-inline" method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn btn-primary rounded-pill">
            Resend Verification Email
        </button>
    </form>
</div>
@endsection
