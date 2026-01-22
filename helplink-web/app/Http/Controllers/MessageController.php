<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function index($userId, Request $request)
{
    $requestId = $request->query('request_id'); // Ambil dari URL kalau ada

    // Tandakan semua mesej dari user tersebut sebagai DIBACA
    $query = Message::where('sender_id', $userId)
        ->where('receiver_id', Auth::id())
        ->where('is_read', false);

    if ($requestId) {
        $query->where('request_id', $requestId);
    }

    $query->update(['is_read' => true]);

    // Dapatkan semua mesej antara dua user (dan request_id kalau ada)
    $messages = Message::where(function ($q) use ($userId, $requestId) {
        $q->where('sender_id', Auth::id())
          ->where('receiver_id', $userId);
        if ($requestId) $q->where('request_id', $requestId);
    })->orWhere(function ($q) use ($userId, $requestId) {
        $q->where('sender_id', $userId)
          ->where('receiver_id', Auth::id());
        if ($requestId) $q->where('request_id', $requestId);
    })->orderBy('created_at', 'asc')->get();

    $receiver = User::findOrFail($userId);

    return view('chat.show', compact('messages', 'receiver', 'requestId'));
}


public function store(Request $request)
{
    $request->validate([
        'receiver_id' => 'required|exists:users,id',
        'message' => 'required|string',
        'request_id' => 'nullable|exists:requests,id',
    ]);

    Message::create([
        'sender_id' => Auth::id(),
        'receiver_id' => $request->receiver_id,
        'message' => $request->message,
        'request_id' => $request->request_id, // ➕ kaitkan dengan request
        'is_read' => false, // mesej baru belum dibaca
    ]);

    return back();
}


    public function inbox()
{
    $userId = Auth::id();

    $messages = Message::where('sender_id', $userId)
        ->orWhere('receiver_id', $userId)
        ->orderBy('created_at', 'asc')
        ->get();

    $conversations = $messages->groupBy(function ($msg) use ($userId) {
        return $msg->sender_id == $userId ? $msg->receiver_id : $msg->sender_id;
    });

    // Buat array untuk label "unread" setiap user
    $unreadFlags = [];

    foreach ($conversations as $partnerId => $msgs) {
        $hasUnread = $msgs->where('sender_id', $partnerId)
                          ->where('receiver_id', $userId)
                          ->where('is_read', false)
                          ->count() > 0;
        $unreadFlags[$partnerId] = $hasUnread;
    }

    return view('chat.inbox', compact('conversations', 'unreadFlags'));
}

//BAHAGAIAN CHAT REQUEST
public function chatRequest($id)
{
    $requestModel = \App\Models\Request::findOrFail($id);

    // Ensure current user is part of the request (owner or claimer)
    $user = Auth::user();
    if ($user->id !== $requestModel->user_id && $user->id !== optional($requestModel->claimedBy)->id) {
        abort(403);
    }

    $receiverId = $user->id === $requestModel->user_id
        ? optional($requestModel->claimedBy)->id
        : $requestModel->user_id;

    if (!$receiverId) {
        return back()->with('error', 'No one has claimed this request yet.');
    }

    // Mark messages as read
    Message::where('sender_id', $receiverId)
        ->where('receiver_id', $user->id)
        ->where('request_id', $id)
        ->where('is_read', false)
        ->update(['is_read' => true]);

    $messages = Message::where(function ($q) use ($user, $receiverId, $id) {
        $q->where('sender_id', $user->id)->where('receiver_id', $receiverId);
    })->orWhere(function ($q) use ($user, $receiverId, $id) {
        $q->where('sender_id', $receiverId)->where('receiver_id', $user->id);
    })->where('request_id', $id)
    ->orderBy('created_at', 'asc')
    ->get();

    $receiver = \App\Models\User::findOrFail($receiverId);

    return view('chat.show', compact('messages', 'receiver'))->with('requestId', $id);
}

public function sendRequestMessage(Request $request, $id)
{
    $request->validate([
        'message' => 'required|string',
    ]);

    $requestModel = \App\Models\Request::findOrFail($id);

    $user = Auth::user();
    if ($user->id !== $requestModel->user_id && $user->id !== optional($requestModel->claimedBy)->id) {
        abort(403);
    }

    $receiverId = $user->id === $requestModel->user_id
        ? optional($requestModel->claimedBy)->id
        : $requestModel->user_id;

    if (!$receiverId) {
        return back()->with('error', 'Receiver not found.');
    }

    Message::create([
        'sender_id' => $user->id,
        'receiver_id' => $receiverId,
        'request_id' => $id,
        'message' => $request->message,
        'is_read' => false,
    ]);

    return redirect()->route('request.chat', $id);
}



}
