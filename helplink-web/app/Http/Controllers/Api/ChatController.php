<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Offer;
use App\Models\Request as HelpRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    // =====================================================
    // START / GET CONVERSATION
    // =====================================================
    public function startConversation(Request $request)
{
    try {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $request->validate([
            'offer_id'   => 'nullable|integer',
            'request_id' => 'nullable|exists:requests,id',
        ]);

        if (!$request->offer_id && !$request->request_id) {
            return response()->json([
                'success' => false,
                'message' => 'offer_id or request_id required'
            ], 422);
        }

        // =============================
        // RESOLVE OTHER USER
        // =============================
        if ($request->offer_id) {
            $offer = Offer::where('offer_id', $request->offer_id)->first();
            if (!$offer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Offer not found'
                ], 404);
            }
            $otherUserId = $offer->user_id;
        } else {
            $req = HelpRequest::findOrFail($request->request_id);
            $otherUserId = $req->user_id;
        }

        if ($user->id === $otherUserId) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot chat with yourself'
            ], 422);
        }

        $u1 = min($user->id, $otherUserId);
        $u2 = max($user->id, $otherUserId);

        // =============================
        // FIND EXISTING CONVERSATION
        // =============================
        $conversation = Conversation::where('user1_id', $u1)
            ->where('user2_id', $u2)
            ->when($request->offer_id, fn ($q) =>
                $q->where('offer_id', $request->offer_id)
            )
            ->when($request->request_id, fn ($q) =>
                $q->where('request_id', $request->request_id)
            )
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'user1_id' => $u1,
                'user2_id' => $u2,
                'offer_id' => $request->offer_id,
                'request_id' => $request->request_id,
                'unread_by_user1' => 0,
                'unread_by_user2' => 0,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $conversation->id,
                'user1_id' => $conversation->user1_id,
                'user2_id' => $conversation->user2_id,
                'offer_id' => $conversation->offer_id,
                'request_id' => $conversation->request_id,
            ]
        ], 200);

    } catch (\Exception $e) {
        Log::error('Chat start error: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Conversation not created'
        ], 500);
    }
}


    // =====================================================
    // SEND MESSAGE
    // =====================================================
    public function sendMessage(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['success' => false], 401);
            }

            $request->validate([
                'conversation_id' => 'required|exists:conversations,id',
                'message' => 'required|string',
            ]);

            $conversation = Conversation::findOrFail($request->conversation_id);

            if (!in_array($user->id, [$conversation->user1_id, $conversation->user2_id])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $msg = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'message' => $request->message,
            ]);

            $conversation->update([
                'last_message' => $request->message,
            ]);

            if ($user->id === $conversation->user1_id) {
                $conversation->increment('unread_by_user2');
            } else {
                $conversation->increment('unread_by_user1');
            }

            return response()->json([
                'success' => true,
                'data' => $msg
            ], 201);

        } catch (\Exception $e) {
            Log::error('Send message error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to send message'
            ], 500);
        }
    }

    // =====================================================
    // GET MESSAGES (RESET UNREAD)
    // =====================================================
    public function getMessages(Request $request, $conversationId)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['success' => false], 401);
            }

            $conversation = Conversation::findOrFail($conversationId);

            if (!in_array($user->id, [$conversation->user1_id, $conversation->user2_id])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            if ($user->id === $conversation->user1_id) {
                $conversation->update(['unread_by_user1' => 0]);
            } else {
                $conversation->update(['unread_by_user2' => 0]);
            }

            $messages = Message::where('conversation_id', $conversationId)
                ->orderBy('created_at', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $messages
            ]);

        } catch (\Exception $e) {
            Log::error('Get messages error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load messages'
            ], 500);
        }
    }

    // =====================================================
    // MY CONVERSATIONS (CHAT LIST)
    // =====================================================
    public function myConversations(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['success' => false], 401);
            }

            $conversations = Conversation::with([
                    'user1:id,name',
                    'user2:id,name'
                ])
                ->where('user1_id', $user->id)
                ->orWhere('user2_id', $user->id)
                ->orderBy('updated_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $conversations
            ]);

        } catch (\Exception $e) {
            Log::error('My conversations error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load conversations'
            ], 500);
        }
    }
}
