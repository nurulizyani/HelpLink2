@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-4">Edit Profile</h3>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    <form action="{{ route('user.profile.update') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label for="name" class="form-label">Full Name</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="phone_number" class="form-label">Phone Number</label>
            <input type="text" name="phone_number" value="{{ old('phone_number', $user->phone_number) }}" class="form-control">
        </div>

        <div class="mb-3">
            <label for="address" class="form-label">Address</label>
            <input type="text" name="address" value="{{ old('address', $user->address) }}" class="form-control">
        </div>

        <button type="submit" class="btn btn-primary">Update Profile</button>
        <a href="{{ route('dashboard') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
