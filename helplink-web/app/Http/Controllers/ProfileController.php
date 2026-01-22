<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        return view('user.profile', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
        ]);

        $user->update([
            'name' => $request->name,
            'address' => $request->address,
            'phone_number' => $request->phone_number,
        ]);

        return redirect()->route('dashboard')->with('success', 'Profile updated successfully!');
    }
}
