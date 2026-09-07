<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Services\AdminActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function create()
    {
        return view('feedback');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'comments' => 'required|string|max:5000',
        ]);

        $feedback = Feedback::create([
            'name' => $request->name,
            'email' => $request->email,
            'source' => 'web',
            'comments' => $request->comments,
        ]);

        $preview = \Illuminate\Support\Str::limit((string) $request->comments, 120);
        app(AdminActivityLogger::class)->feedbackSubmitted($preview, $feedback);

        return redirect()->back()->with('success', 'Thank you! Your feedback has been submitted.');
    }

    public function index()
    {
        $feedbacks = Feedback::query()
            ->with('student:id,id_number,firstname,lastname')
            ->latest()
            ->paginate(10);
        $unreadCount = Feedback::query()->unread()->count();

        return view('feedbacks.index', compact('feedbacks', 'unreadCount'));
    }

    public function markRead(Feedback $feedback): RedirectResponse
    {
        if ($feedback->read_at === null) {
            $feedback->update(['read_at' => now()]);
        }

        return back()->with('success', 'Feedback marked as read.');
    }

    public function markAllRead(): RedirectResponse
    {
        Feedback::query()->unread()->update(['read_at' => now()]);

        return back()->with('success', 'All feedback marked as read.');
    }
}
