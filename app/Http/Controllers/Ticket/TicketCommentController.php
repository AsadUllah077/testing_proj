<?php

namespace App\Http\Controllers\Ticket;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketCommentController extends Controller
{
    public function store(Request $request, Ticket $ticket)
    {
        $request->validate([
            'body'       => 'required|string',
            'is_private' => 'nullable|boolean',
        ]);

        $comment = $ticket->comments()->create([
            'user_id'    => Auth::id(),
            'body'       => $request->body,
            'is_private' => $request->boolean('is_private', false),
        ]);

        $comment->load('user');

        $userName = $comment->user->full_name ?? ($comment->user->name ?? 'Unknown');

        return response()->json([
            'success' => true,
            'message' => 'Comment posted.',
            'comment' => [
                'id'         => $comment->id,
                'body'       => $comment->body,
                'is_private' => $comment->is_private,
                'user_name'  => $userName,
                'initials'   => strtoupper(mb_substr($userName, 0, 2)),
                'created_at' => $comment->created_at->format('d M Y, H:i'),
            ],
        ]);
    }

    public function destroy(Ticket $ticket, TicketComment $comment)
    {
        if ($comment->user_id !== Auth::id() && !Auth::user()->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $comment->delete();
        return response()->json(['success' => true, 'message' => 'Comment deleted.']);
    }
}
