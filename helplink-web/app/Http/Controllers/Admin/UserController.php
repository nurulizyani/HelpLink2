<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    // List all users
    public function index()
    {
        $users = User::orderBy('created_at', 'desc')->get();
        return view('admin.users.index', compact('users'));
    }

    // Show single user
    public function show($id)
    {
        $user = User::findOrFail($id);
        return view('admin.users.show', compact('user'));
    }

    // Edit user form
    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('admin.users.edit', compact('user'));
    }

    // Update user data
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
        ]);


        // ✅ Optional: notify user only if you want transparency
        // NotificationHelper::send(
        //     $user->id,
        //     'Profile Updated',
        //     'Your profile has been updated by the admin.',
        //     'system'
        // );

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    // Delete user
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('admin.users.index')->with('error', 'User deleted.');
    }

    //export
    public function export()
    {
        $users = User::latest()->get();

        $filename = 'users_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
        ];

        $callback = function () use ($users) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'User ID',
                'Name',
                'Email',
                'Email Status',
                'Joined Date'
            ]);

            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->email_verified_at ? 'Verified' : 'Unverified',
                    $user->created_at->format('d M Y')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

}
