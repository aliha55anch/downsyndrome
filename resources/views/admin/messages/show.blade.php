@extends('layouts.admin')

@section('title', 'Contact Message')

@section('content')
<div class="page-title admin-dashboard-heading admin-message-detail-heading">
    <div><a class="admin-message-back-link" href="{{ route('admin.messages.index') }}"><span aria-hidden="true">←</span> Contact messages</a><span class="eyebrow">Family support inbox</span><h1>{{ $message->subject }}</h1><p>Submitted {{ $message->created_at->format('M j, Y \a\t g:i A') }}</p></div>
    <span class="admin-message-status {{ $message->is_read ? 'is-read' : 'is-unread' }}">{{ $message->is_read ? 'Read' : 'Unread' }}</span>
</div>

<article class="admin-panel admin-message-detail">
    <div class="admin-message-sender-card">
        <span class="admin-message-avatar" aria-hidden="true">{{ strtoupper(substr($message->name, 0, 1)) }}</span>
        <div><span class="eyebrow">From</span><strong>{{ $message->name }}</strong><a href="mailto:{{ $message->email }}">{{ $message->email }}</a></div>
        <a class="btn btn-outline admin-message-reply" href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: '.$message->subject) }}">Reply by email</a>
    </div>

    <div class="admin-message-content">
        <span class="eyebrow">Message</span>
        <p>{{ $message->message }}</p>
    </div>

    <div class="admin-message-detail-actions">
        <form method="POST" action="{{ route('admin.messages.read', $message) }}">@csrf @method('PATCH')<button class="btn btn-blue" type="submit">Mark {{ $message->is_read ? 'unread' : 'read' }}</button></form>
        <a class="btn btn-outline" href="{{ route('admin.messages.index') }}">Back to inbox</a>
        <form method="POST" action="{{ route('admin.messages.destroy', $message) }}" onsubmit="return confirm('Delete this contact message? This cannot be undone.');">@csrf @method('DELETE')<button class="btn btn-red" type="submit">Delete message</button></form>
    </div>
</article>
@endsection
