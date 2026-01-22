<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request as HttpRequest;
use App\Models\Request as HelpRequest;
use App\Models\User;
use App\Helpers\NotificationHelper;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminRequestController extends Controller
{
    /**
     * =========================
     * LIST REQUESTS (WITH FILTER)
     * =========================
     */
    public function index(HttpRequest $request)
    {
        $query = HelpRequest::with('user')
            ->orderByDesc('created_at');

        // 🔍 FILTER BY STATUS
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $requests = $query->get();

        return view('admin.requests.index', compact('requests'));
    }

    /**
     * =========================
     * VIEW REQUEST DETAILS
     * =========================
     */
    public function show($id)
    {
        $request = HelpRequest::with([
            'user',
            'images',
            'claimRequests', // ✔ betul ikut model
        ])->findOrFail($id);

        return view('admin.requests.show', compact('request'));
    }

    /**
     * =========================
     * UPDATE STATUS (APPROVE / REJECT)
     * =========================
     */
    public function updateStatus(HttpRequest $request, $id)
    {
        $req = HelpRequest::findOrFail($id);

        $request->validate([
            'status' => 'required|in:approved,rejected,fulfilled',
            'admin_remark' => 'nullable|string|max:1000',
        ]);

        $status = strtolower($request->status);
        $req->status = $status;

        if ($request->filled('admin_remark')) {
            $req->admin_remark = $request->admin_remark;
        }

        $req->save();

        // 🔔 Notify User
        if ($req->user) {
            NotificationHelper::send(
                $req->user->id,
                'Request ' . ucfirst($status),
                "Your request '{$req->item_name}' has been {$status}.",
                'request'
            );
        }

        // 🔔 Notify Admin (Log)
        $admin = auth('admin')->user();
        if ($admin) {
            NotificationHelper::send(
                $admin->id,
                'Request Updated',
                "You have {$status} the request '{$req->item_name}'.",
                'system'
            );
        }

        return redirect()
            ->route('admin.requests.index', ['status' => $status])
            ->with('success', 'Request status updated successfully.');
    }

    public function export()
    {
        $fileName = 'requests_' . now()->format('Ymd_His') . '.csv';

        $requests = \App\Models\Request::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename={$fileName}",
        ];

        $callback = function () use ($requests) {
            $handle = fopen('php://output', 'w');

            // CSV HEADER
            fputcsv($handle, [
                'ID',
                'User Name',
                'User Email',
                'Item',
                'Category',
                'Status',
                'Admin Remark',
                'Created At',
            ]);

            foreach ($requests as $req) {
                fputcsv($handle, [
                    $req->id,
                    $req->user->name ?? '-',
                    $req->user->email ?? '-',
                    $req->item_name,
                    $req->category,
                    $req->status,
                    $req->admin_remark ?? '',
                    $req->created_at,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
