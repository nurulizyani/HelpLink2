<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    // ─── Display Report Form ─────────────────────────────────────
    public function create($id)
    {
        $user = User::findOrFail($id); // user yang dilaporkan
        return view('report.create', compact('user'));
    }

    // ─── Store New Report ────────────────────────────────────────
    public function store(Request $request, $id = null)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
            'evidence' => 'nullable|image|mimes:jpg,jpeg,png|max:2048', // Max 2MB
        ]);

        $imagePath = null;

        if ($request->hasFile('evidence')) {
            $imagePath = $request->file('evidence')->store('report_images', 'public');
        } else {
            $imagePath = null;
        }

        Report::create([
            'reported_user_id' => $id ?? $request->reported_user_id,
            'reporter_id' => Auth::id(),
            'reason' => $request->reason,
            'image' => $imagePath,
        ]);

        // semak jika report dibuat untuk claim offer
$redirectTo = $request->has('claim_id')
    ? route('claims.rate', $request->claim_id)
    : route('dashboard');

        return redirect($redirectTo)->with('success', 'User has been reported successfully.');
    }

    // ─── Admin: View All Reports ────────────────────────────────
    public function adminIndex()
    {
        $reports = Report::with(['reporter', 'reportedUser'])->latest()->get();
        return view('admin.reports.index', compact('reports'));
    }
}
