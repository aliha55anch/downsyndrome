@extends('layouts.admin')

@section('title', 'Contact Messages')

@section('content')
<div class="page-title">
    <h1>Contact Messages</h1>
    <p>Messages submitted through the public contact form.</p>
</div>

<div class="btn-row">
    <a class="btn {{ $unread ? 'btn-blue' : 'btn-outline' }}" href="{{ route('admin.messages.index', ['unread' => 1]) }}">Unread only</a>
    <a class="btn {{ ! $unread ? 'btn-blue' : 'btn-outline' }}" href="{{ route('admin.messages.index') }}">All messages</a>
</div>

<div class="table-wrap">
    <table>
        <thead><tr><th>Subject</th><th>Sender</th><th>Submitted</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse ($messages as $message)
            <tr>
                <td>{{ $message->subject }}</td>
                <td>{{ $message->name }}<br><span class="muted">{{ $message->email }}</span></td>
                <td>{{ $message->created_at->format('Y-m-d H:i') }}</td>
                <td>{{ $message->is_read ? 'Read' : 'Unread' }}</td>
                <td>
                    <a class="btn btn-outline btn-small" href="{{ route('admin.messages.show', $message) }}">View</a>
                    <form method="POST" action="{{ route('admin.messages.read', $message) }}" style="display:inline;">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-blue btn-small" type="submit">Mark {{ $message->is_read ? 'unread' : 'read' }}</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5">No contact messages yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{ $messages->links() }}
@endsection
