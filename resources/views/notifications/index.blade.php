@extends('layouts.dashboard')
@section('title', 'Notifications')
@section('heading', 'Notifications')
@section('dashboard-content')
<section class="doctor-panel"><h2>Your updates</h2>@forelse($notifications as $notification)<article class="doctor-panel {{ $notification->read_at ? '' : 'is-unread' }}"><h3>{{ $notification->data['title'] ?? 'Account update' }}</h3><p>{{ $notification->data['message'] ?? '' }}</p><small>{{ $notification->created_at->diffForHumans() }}</small>@if(!$notification->read_at)<form method="POST" action="{{ route('notifications.read', $notification->id) }}">@csrf @method('PATCH')<button class="btn btn-outline btn-small">Mark as read and open</button></form>@elseif(!empty($notification->data['url']))<p><a class="btn btn-outline btn-small" href="{{ $notification->data['url'] }}">Open related item</a></p>@endif</article>@empty<p>No notifications yet.</p>@endforelse{{ $notifications->links() }}</section>
@endsection
