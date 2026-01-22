<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    use RegistersUsers;

    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Validate incoming registration request.
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:20'],
        ]);
    }

    /**
     * Create a new user instance.
     */
    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'address' => $data['address'] ?? null,
            'phone_number' => $data['phone_number'] ?? null,
        ]);
    }

    /**
     * After registration, logout and redirect to login page with message.
     */
    protected function registered(Request $request, $user)
    {
        $this->guard()->logout();
        return redirect()->route('login', ['registered' => 1]);
    }
}
