<!-- Header Bar -->
    <header class="nxl-header">
        <div class="header-wrapper">
            <div class="header-left">
                <a href="javascript:void(0);" id="menu-mini-button" class="header-single-toggle-btn"
                    title="Toggle Sidebar Navigation">
                    <i class="feather-menu"></i>
                </a>
                <div class="header-title-block ms-2">
                    <h4 class="mb-0 fw-bold">@yield('title', 'Dashboard')</h4>
                </div>
            </div>

            <div class="header-right">
                <!-- NOTIFICATION HOVER POPUP -->
                <div class="header-action-bell-wrapper">
                    <div class="header-action-bell" title="Notifications">
                        <i class="feather-bell"></i>
                        @php
                            $unreadCount = auth()->user()?->unreadNotifications()->count() ?? 0;
                        @endphp
                        <span class="header-bell-badge" id="notif-badge" @if($unreadCount === 0) style="display:none" @endif>
                            {{ $unreadCount }}
                        </span>
                    </div>
                    <div class="header-notification-dropdown">
                        <div class="notification-header">
                            <h6 class="fw-bold text-dark mb-0 fs-13">Notifications</h6>
                            <span class="badge bg-soft-primary text-primary fs-10" id="notif-header-badge">
                                {{ $unreadCount > 0 ? $unreadCount . ' New' : 'All read' }}
                            </span>
                        </div>
                        <div class="notification-list" id="notification-list">
                            @forelse(auth()->user()?->notifications()->latest()->take(15)->get() ?? [] as $notification)
                                @php
                                    $icon = match($notification->data['icon'] ?? '') {
                                        'new-lead'     => 'feather-user-plus',
                                        'stage-change' => 'feather-refresh-cw',
                                        'calendar'     => 'feather-calendar',
                                        default        => 'feather-bell',
                                    };
                                    $colorClass = match($notification->data['icon'] ?? '') {
                                        'new-lead'     => 'bg-soft-primary text-primary',
                                        'stage-change' => 'bg-soft-warning text-warning',
                                        'calendar'     => 'bg-soft-info text-info',
                                        default        => 'bg-soft-secondary text-secondary',
                                    };
                                @endphp
                                <a href="{{ $notification->data['url'] ?? '#' }}" class="notification-item {{ $notification->read_at ? '' : 'unread' }}">
                                    <div class="avatar-text {{ $colorClass }} rounded-circle flex-shrink-0">
                                        <i class="{{ $icon }}"></i>
                                    </div>
                                    <div>
                                        <div class="fs-12 fw-bold text-dark">{{ $notification->data['title'] ?? 'Notification' }}</div>
                                        <div class="fs-11 text-muted">{{ $notification->data['message'] ?? '' }}</div>
                                        <div class="fs-10 text-muted mt-1">{{ $notification->created_at ? $notification->created_at->diffForHumans() : '' }}</div>
                                    </div>
                                </a>
                            @empty
                                <div class="text-center text-muted py-3 fs-12">No notifications yet</div>
                            @endforelse
                        </div>
                        @if($unreadCount > 0)
                        <div class="p-2 border-top text-center">
                            <form action="{{ route('notifications.markAllRead') }}" method="POST" class="m-0 p-0">
                                @csrf
                                <button type="submit" class="btn btn-link fs-11 text-primary text-decoration-none p-0 m-0 border-0 bg-transparent">Mark all as read</button>
                            </form>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- USER AVATAR HOVER DROPDOWN -->
                <div class="header-user-profile-wrapper">
                    <div class="d-flex align-items-center gap-2">
                        <div class="header-avatar-circle">
                            <img src="{{ asset('assets/images/user-avatar.svg') }}" alt="User Avatar" />
                        </div>
                        <div class="d-none d-md-block text-start">
                            <div class="fs-13 fw-bold text-dark mb-0" style="line-height: 1.1;">
                                {{ auth()->user()?->name ?? 'User' }}
                                <i class="feather-chevron-down ms-1 fs-11 text-muted"></i>
                            </div>
                            <div class="fs-11 text-muted fw-semibold">
                                {{ auth()->user()?->role?->name ?? (auth()->user()?->isSuperAdmin() ? 'Super Administrator' : 'User') }}
                            </div>
                        </div>
                    </div>
                    <div class="header-user-dropdown">
                        <div class="px-3 py-2 border-bottom mb-1">
                            <div class="fw-bold text-dark fs-13">{{ auth()->user()?->name ?? 'User' }}</div>
                            <div class="fs-11 text-muted">{{ auth()->user()?->email ?? '' }}</div>
                        </div>
                        <button class="user-dropdown-item" data-bs-toggle="modal" data-bs-target="#resetPasswordModal">
                            <i class="feather-key text-primary me-2"></i> Reset Password
                        </button>
                        <button class="user-dropdown-item text-danger" data-bs-toggle="modal"
                            data-bs-target="#logoutModal">
                            <i class="feather-log-out text-danger me-2"></i> Logout Account
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </header>

@auth
@if(config('broadcasting.connections.pusher.key'))
<!-- Pusher Real-Time Notifications -->
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
(function () {
    const pusherKey = "{{ config('broadcasting.connections.pusher.key') }}";
    const pusherCluster = "{{ config('broadcasting.connections.pusher.options.cluster', 'mt1') }}";
    const userId = {{ auth()->id() }};

    const pusher = new Pusher(pusherKey, { cluster: pusherCluster, forceTLS: true });

    // Subscribe to the private user channel
    const channel = pusher.subscribe('private-App.Models.User.' + userId);

    channel.bind('Illuminate\\Notifications\\Events\\BroadcastNotificationCreated', function (data) {
        // --- Update badge count ---
        const badge = document.getElementById('notif-badge');
        const headerBadge = document.getElementById('notif-header-badge');
        let current = parseInt(badge.textContent.trim()) || 0;
        current++;
        badge.textContent = current;
        badge.style.display = '';
        if (headerBadge) { headerBadge.textContent = current + ' New'; }

        // --- Determine icon & color ---
        const iconMap = {
            'new-lead':     { icon: 'feather-user-plus',   color: 'bg-soft-primary text-primary' },
            'stage-change': { icon: 'feather-refresh-cw',  color: 'bg-soft-warning text-warning' },
            'calendar':     { icon: 'feather-calendar',     color: 'bg-soft-info text-info' },
        };
        const style = iconMap[data.icon] || { icon: 'feather-bell', color: 'bg-soft-secondary text-secondary' };

        // --- Prepend notification item ---
        const list = document.getElementById('notification-list');
        const item = document.createElement('a');
        const notificationUrl = new URL(data.url || '#', window.location.origin);
        item.href = notificationUrl.origin === window.location.origin ? notificationUrl.href : '#';
        item.className = 'notification-item unread';
        item.innerHTML = `
            <div class="avatar-text ${style.color} rounded-circle flex-shrink-0">
                <i class="${style.icon}"></i>
            </div>
            <div>
                <div class="fs-12 fw-bold text-dark notification-title"></div>
                <div class="fs-11 text-muted notification-message"></div>
                <div class="fs-10 text-muted mt-1">Just now</div>
            </div>`;
        item.querySelector('.notification-title').textContent = data.title ?? '';
        item.querySelector('.notification-message').textContent = data.message ?? '';
        list.prepend(item);

        // --- Show a toast-style alert ---
        const toast = document.createElement('div');
        toast.className = 'pusher-toast';
        toast.innerHTML = '<strong class="notification-title"></strong><br><span class="notification-message"></span>';
        toast.querySelector('.notification-title').textContent = data.title ?? '';
        toast.querySelector('.notification-message').textContent = data.message ?? '';
        document.body.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 100);
        setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 400); }, 5000);
    });
})();
</script>
<style>
.pusher-toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    background: #fff;
    border-left: 4px solid #5b8dee;
    border-radius: 10px;
    box-shadow: 0 8px 32px rgba(91,141,238,.18);
    padding: 14px 20px;
    font-size: 13px;
    color: #2d3a5a;
    z-index: 99999;
    max-width: 320px;
    opacity: 0;
    transform: translateY(20px);
    transition: opacity .3s, transform .3s;
}
.pusher-toast.show { opacity: 1; transform: translateY(0); }
.notification-item.unread { background: rgba(91,141,238,.05); }
</style>
@endif
@endauth
