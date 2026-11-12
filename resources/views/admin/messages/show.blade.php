@extends('layouts.admin')

@section('title', 'Contact Message')

@section('content')
<div class="detail-box">
    <h1>{{ $message->subject }}</h1>
    <p class="muted">From {{ $message->name }} ({{ $message->email }}) on {{ $message->created_at->format('Y-m-d H:i') }}</p>
    <hr>
    <p style="white-space:pre-wrap;">{{ $message->message }}</p>
    <div class="btn-row">
        <form method="POST" action="{{ route('admin.messages.read', $message) }}">
            @csrf
            @method('PATCH')
            <button class="btn btn-blue" type="submit">Mark {{ $message->is_read ? 'unread' : 'read' }}</button>
        </form>
        <a class="btn btn-outline" href="{{ route('admin.messages.index') }}">Back to messages</a>
    </div>
</div>
@endsection
