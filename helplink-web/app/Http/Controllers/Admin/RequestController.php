<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request as HttpRequest;
use App\Models\Request as UserRequest;
use App\Models\RequestImage;
use App\Models\User;
use App\Helpers\NotificationHelper;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

class RequestController extends Controller
{
    /**
     * Display all requests (Admin)
     */
    public function index()
    {
        $requests = UserRequest::with('user')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.requests.index', compact('requests'));
    }

    /**
     * Display a single request with images & documents
     */
    public function show($id)
    {
        $request = UserRequest::with([
            'user',
            'images'
        ])->findOrFail($id);

        return view('admin.requests.show', compact('request'));
    }

    /**
     * Approve request
     */
    public function approve($id)
    {
        $request = UserRequest::with('user')->findOrFail($id);

        if ($request->status !== 'pending') {
            return redirect()
                ->back()
                ->with('error', 'Only pending requests can be approved.');
        }

        $request->status = 'approved';
        $request->save();

        // Notify user
        NotificationHelper::send(
            $request->user_id,
            'Request Approved',
            "Your request '{$request->item_name}' has been approved.",
            'request'
        );

        return redirect()
            ->route('admin.requests.index')
            ->with('success', 'Request approved successfully.');
    }

    /**
     * Reject request
     */
    public function reject(HttpRequest $httpRequest, $id)
    {
        $request = UserRequest::with('user')->findOrFail($id);

        if ($request->status !== 'pending') {
            return redirect()
                ->back()
                ->with('error', 'Only pending requests can be rejected.');
        }

        $request->status = 'rejected';
        $request->save();

        // Notify user
        NotificationHelper::send(
            $request->user_id,
            'Request Rejected',
            "Your request '{$request->item_name}' has been rejected.",
            'request'
        );

        return redirect()
            ->route('admin.requests.index')
            ->with('error', 'Request rejected.');
    }

    /**
     * Export all requests to CSV
     */
    public function export()
    {
        $requests = UserRequest::with('user')
            ->orderByDesc('created_at')
            ->get();

        $csvData = [];
        $csvData[] = [
            'ID',
            'User Name',
            'User Email',
            'Item Name',
            'Category',
            'Status',
            'Address',
            'Submitted At'
        ];

        foreach ($requests as $request) {
            $csvData[] = [
                $request->id,
                $request->user->name ?? '-',
                $request->user->email ?? '-',
                $request->item_name,
                $request->category,
                ucfirst($request->status),
                $request->address,
                $request->created_at->format('d M Y, h:i A'),
            ];
        }

        $filename = 'requests_' . now()->format('Ymd_His') . '.csv';

        $handle = fopen('php://temp', 'r+');

        foreach ($csvData as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);

        return response()->streamDownload(
            function () use ($handle) {
                fpassthru($handle);
            },
            $filename,
            ['Content-Type' => 'text/csv']
        );
    }
}
