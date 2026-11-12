@php($recentNotifications = auth()->user()->notifications()->latest()->limit(5)->get())
<details class="notification-bell">
    <summary aria-label="Notifications, {{ auth()->user()->unreadNotifications()->count() }} unread">
        <svg aria-hidden="true" viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg>
        @if(auth()->user()->unreadNotifications()->count())<b class="notification-count">{{ auth()->user()->unreadNotifications()->count() > 99 ? '99+' : auth()->user()->unreadNotifications()->count() }}</b>@endif
    </summary>
    <div class="notification-popover" role="region" aria-label="Recent notifications">
        <div class="panel-heading"><strong>Notifications</strong><form method="POST" action="{{ $readAllRoute }}">@csrf @method('PATCH')<button class="text-button" type="submit">Mark all read</button></form></div>
        @forelse($recentNotifications as $notification)
            <article class="notification-item {{ $notification->read_at ? '' : 'is-unread' }}">
                <div><strong>{{ $notification->data['title'] ?? 'Update' }}</strong><p>{{ $notification->data['message'] ?? '' }}</p><time>{{ $notification->created_at->diffForHumans() }}</time></div>
                @if(!$notification->read_at || !empty($notification->data['url']))<form method="POST" action="{{ route($readRouteName, $notification->id) }}">@csrf @method('PATCH')<button class="text-button" type="submit">{{ !empty($notification->data['url']) ? 'Open' : 'Mark read' }}</button></form>@endif
            </article>
        @empty<p class="muted">You’re all caught up.</p>@endforelse
        <a class="notification-view-all" href="{{ $indexRoute }}">View all notifications</a>
    </div>
</details>
