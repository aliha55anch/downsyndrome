@extends('layouts.admin')

@section('title', 'Contact Messages')

@section('content')
<div class="page-title admin-dashboard-heading admin-messages-heading">
    <div><span class="eyebrow">Family support inbox</span><h1>Contact Messages</h1><p>Review and manage messages submitted through the public contact form.</p></div>
    <span class="admin-users-count">{{ $messages->total() }} {{ \Illuminate\Support\Str::plural('message', $messages->total()) }}</span>
</div>

<nav class="admin-message-tabs" aria-label="Filter contact messages">
    <a class="{{ $unread ? '' : 'is-active' }}" href="{{ route('admin.messages.index') }}" @if(! $unread) aria-current="page" @endif>All messages</a>
    <a class="{{ $unread ? 'is-active' : '' }}" href="{{ route('admin.messages.index', ['unread' => 1]) }}" @if($unread) aria-current="page" @endif>Unread only</a>
</nav>

<section class="admin-panel admin-messages-panel">
    <div class="panel-heading"><div><span class="eyebrow">Inbox</span><h2>{{ $unread ? 'Unread messages' : 'Recent messages' }}</h2><p>Open a message to read it and update its status.</p></div></div>
    <div class="table-wrap admin-messages-table-wrap">
        <table class="admin-messages-table">
            <thead><tr><th scope="col">Subject</th><th scope="col">Sender</th><th scope="col">Submitted</th><th scope="col">Status</th><th scope="col">Actions</th></tr></thead>
            <tbody>
            @forelse ($messages as $message)
                <tr>
                    <td class="admin-message-subject"><strong>{{ $message->subject }}</strong></td>
                    <td class="admin-message-sender"><strong>{{ $message->name }}</strong><a href="mailto:{{ $message->email }}">{{ $message->email }}</a></td>
                    <td><time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('M j, Y') }}<span>{{ $message->created_at->format('g:i A') }}</span></time></td>
                    <td><span class="admin-message-status {{ $message->is_read ? 'is-read' : 'is-unread' }}">{{ $message->is_read ? 'Read' : 'Unread' }}</span></td>
                    <td><div class="admin-message-actions"><a class="btn btn-outline btn-small" href="{{ route('admin.messages.show', $message) }}">Open message</a><form method="POST" action="{{ route('admin.messages.read', $message) }}">@csrf @method('PATCH')<button class="btn btn-blue btn-small" type="submit">Mark {{ $message->is_read ? 'unread' : 'read' }}</button></form><form method="POST" action="{{ route('admin.messages.destroy', $message) }}" onsubmit="return confirm('Delete this contact message? This cannot be undone.');">@csrf @method('DELETE')<button class="btn btn-red btn-small" type="submit">Delete</button></form></div></td>
                </tr>
            @empty
                <tr><td class="admin-messages-empty" colspan="5">{{ $unread ? 'You are all caught up. There are no unread messages.' : 'No contact messages have been received yet.' }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $messages->links() }}
</section>
@endsection
