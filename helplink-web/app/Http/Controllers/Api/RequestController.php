<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Request as RequestModel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Admin;
use App\Helpers\NotificationHelper;
use App\Services\AiRequestAnalyzer;

class RequestController extends Controller
{
    /**
     * =========================
     * CREATE NEW HELP REQUEST
     * =========================
     */
    public function store(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }
            // =========================
            // VALIDATION
            // =========================
            $validated = $request->validate([
                'item_name'   => 'required|string|max:255',
                'description' => 'required|string',
                'category'    => 'required|string|max:100',
                'address'     => 'required|string',
                'latitude'    => 'nullable|numeric',
                'longitude'   => 'nullable|numeric',
                'image'       => 'nullable|file|mimes:jpg,jpeg,png|max:4096',
                'document'    => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            ]);

            // =========================
            // IMAGE UPLOAD
            // =========================
            $imagePath = null;
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $filename = time() . '_img_' . $image->getClientOriginalName();
                $image->storeAs('requests/images', $filename, 'public');
                $imagePath = 'storage/requests/images/' . $filename;
            }

            // =========================
            // DOCUMENT UPLOAD
            // =========================
            $documentPath = null;
            $absoluteDocumentPath = null;

            if ($request->hasFile('document')) {
                $doc = $request->file('document');
                $filename = time() . '_doc_' . $doc->getClientOriginalName();
                $doc->storeAs('requests/documents', $filename, 'public');
                $documentPath = 'storage/requests/documents/' . $filename;

                $absoluteDocumentPath = storage_path(
                    'app/public/requests/documents/' . $filename
                );
            }

            // =========================
            // CREATE REQUEST
            // =========================
            $newRequest = RequestModel::create([
                'user_id'     => $user->id,
                'item_name'   => $validated['item_name'],
                'description' => $validated['description'],
                'category'    => $validated['category'],
                'address'     => $validated['address'],
                'latitude'    => $validated['latitude'] ?? null,
                'longitude'   => $validated['longitude'] ?? null,
                'image'       => $imagePath,
                'document'    => $documentPath,
                'status'      => 'pending',
            ]);

            // =========================
            // AI ANALYSIS (VISION ONLY)
            // =========================
            if ($absoluteDocumentPath && file_exists($absoluteDocumentPath)) {
                try {
                    $aiResult = AiRequestAnalyzer::analyzeDocument(
                        $absoluteDocumentPath,
                        [
                            'item_name'   => $newRequest->item_name,
                            'description' => $newRequest->description,
                            'category'    => $newRequest->category,
                        ]
                    );

                    $newRequest->update([
                        'ai_document_type'  => $aiResult['document_type'] ?? null,
                        'ai_summary'        => $aiResult['summary'] ?? null,
                        'ai_extracted_data' => isset($aiResult['extracted_data'])
                            ? json_encode($aiResult['extracted_data'])
                            : null,
                        'ai_confidence'     => $aiResult['confidence'] ?? null,
                    ]);

                } catch (\Exception $e) {
                    Log::error('AI ANALYSIS ERROR', [
                        'message' => $e->getMessage()
                    ]);
                }
            }

            // =========================
            // NOTIFY ADMIN
            // =========================
            $admin = Admin::first();
            if ($admin) {
                NotificationHelper::send(
                    $admin->admin_id,
                    'New Help Request',
                    "New request '{$newRequest->item_name}' submitted.",
                    'request'
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Request submitted successfully',
                'data'    => $newRequest
            ], 201);

        } catch (\Exception $e) {
            Log::error('REQUEST STORE ERROR', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit request'
            ], 500);
        }
    }

    /**
     * =========================
     * PUBLIC APPROVED REQUESTS
     * =========================
     */
    public function index(Request $request)
    {
        $userLat = $request->latitude;
        $userLng = $request->longitude;
        $radius  = $request->radius ?? 0;

        $requests = RequestModel::with('user')
            ->where('status', 'approved')
            ->get();

        $requests = $requests->map(function ($req) use ($userLat, $userLng) {
            if (!$req->latitude || !$req->longitude || !$userLat || !$userLng) {
                $req->distance = null;
                return $req;
            }

            $req->distance = $this->calculateDistance(
                $userLat,
                $userLng,
                $req->latitude,
                $req->longitude
            );

            return $req;
        });

        if ($radius > 0) {
            $requests = $requests->filter(function ($req) use ($radius) {
                return $req->distance === null || $req->distance <= $radius;
            });
        }

        return response()->json([
            'success' => true,
            'data' => $requests->values()
        ]);
    }

    /**
     * =========================
     * MY REQUESTS
     * =========================
     */
    public function myRequests()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => RequestModel::where('user_id', $user->id)->latest()->get()
        ]);
    }

    /**
     * =========================
     * DISTANCE CALCULATION
     * =========================
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2 +
             cos(deg2rad($lat1)) *
             cos(deg2rad($lat2)) *
             sin($dLon / 2) ** 2;

        return round($earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a))), 2);
    }
}
