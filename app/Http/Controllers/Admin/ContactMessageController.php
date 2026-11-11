<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactMessageController extends Controller
{
    public function index(Request $request): View
    {
        $unread = $request->boolean('unread');

        return view('admin.messages.index', [
            'messages' => ContactMessage::query()
                ->when($unread, fn ($query) => $query->where('is_read', false))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'unread' => $unread,
        ]);
    }

    public function show(ContactMessage $contactMessage): View
    {
        return view('admin.messages.show', ['message' => $contactMessage]);
    }

    public function toggleRead(ContactMessage $contactMessage): RedirectResponse
    {
        $contactMessage->update([
            'is_read' => ! $contactMessage->is_read,
            'read_at' => $contactMessage->is_read ? null : now(),
        ]);

        return back()->with('status', 'Message status updated.');
    }
}
