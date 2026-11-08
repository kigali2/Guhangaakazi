<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request)
    {
        $query = Comment::with('commentable')->latest();

        $status = $request->query('status');
        if (in_array($status, ['approved', 'rejected'], true)) {
            $query->where('status', $status);
        }

        return view('admin.comments.index', [
            'items' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function status(Request $request, Comment $comment)
    {
        $data = $request->validate(['status' => 'required|in:approved,rejected']);
        $comment->status = $data['status'];
        $comment->is_reported = false;
        $comment->save();

        return back()->with('success', $data['status'] === 'approved' ? 'Comment approved.' : 'Comment rejected and hidden.');
    }

    public function destroy(Comment $comment)
    {
        $comment->delete();

        return back()->with('success', 'Comment deleted.');
    }
}
