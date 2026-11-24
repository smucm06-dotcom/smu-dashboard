<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-brand">
        <img src="{{ asset('images/sidebar-logo.png') }}" alt="Chief Minister's Special Monitoring Unit" class="sidebar-logo">
        <!-- Desktop: 260px sidebar <-> 68px icon rail. Mobile: close drawer. -->
        <button
            type="button"
            class="sidebar-brand-toggle"
            id="sidebarToggleButton"
            onclick="toggleSidebar()"
            aria-label="Collapse sidebar"
            aria-controls="appSidebar"
            aria-expanded="true"
            title="Collapse sidebar"
        >
            <span class="sidebar-toggle-icon sidebar-toggle-icon--expanded" aria-hidden="true">
                <i data-lucide="panel-left-close"></i>
            </span>
            <span class="sidebar-toggle-icon sidebar-toggle-icon--collapsed" aria-hidden="true">
                <i data-lucide="panel-left-open"></i>
            </span>
        </button>
    </div>

    @php
        // A call center campaign's page is an initiative's page, but it belongs to the Call Center menu.
        $onCallCenter = request()->routeIs('call-center.*')
            || \App\Support\CallCenterCampaigns::includes(request()->route('initiative') instanceof \App\Models\Initiative ? request()->route('initiative') : null);

        // Shown to admins, and to users given at least one campaign.
        $user = auth()->user();
        $showCallCenter = $user !== null && ($user->isAdmin() || \App\Models\Initiative::callCenter()->where('is_active', true)->visibleTo($user)->exists());
    @endphp

    <nav class="sidebar-nav" aria-label="Main navigation">
        <a href="{{ route('departments.index') }}"
           title="Departments"
           class="nav-link {{ request()->routeIs('departments.*', 'initiatives.*') && ! $onCallCenter ? 'active' : '' }}"
           >
            <span class="nav-icon"><i data-lucide="building-2"></i></span>
            <span class="nav-label">Departments</span>
        </a>

        @if ($showCallCenter)
            <a href="{{ route('call-center.index') }}"
               title="Call Center"
               class="nav-link {{ $onCallCenter ? 'active' : '' }}"
               >
                <span class="nav-icon"><i data-lucide="headset"></i></span>
                <span class="nav-label">Call Center</span>
            </a>
        @endif

        <!-- Reports route is not registered yet; keep the placeholder non-interactive. -->
        <div class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" title="Reports (coming soon)">
            <span class="nav-icon"><i data-lucide="chart-column-increasing"></i></span>
            <span class="nav-label">Reports</span>
        </div>

        @can('manage-users')
            <a href="{{ route('users.index') }}"
               title="Users"
               class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
               >
                <span class="nav-icon"><i data-lucide="users"></i></span>
                <span class="nav-label">Users</span>
            </a>
        @endcan

        <a href="{{ route('settings.index') }}"
           title="Settings"
           class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}"
           >
            <span class="nav-icon"><i data-lucide="settings"></i></span>
            <span class="nav-label">Settings</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user" title="{{ auth()->user()?->name ?? 'User' }}">
            <span class="sidebar-user-avatar">
                {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
            </span>
            <div class="sidebar-user-details">
                <strong title="{{ auth()->user()?->name ?? 'User' }}">{{ auth()->user()?->name ?? 'User' }}</strong>
                <span title="{{ auth()->user()?->email ?? '' }}">{{ auth()->user()?->email ?? '' }}</span>
            </div>
        </div>

        <form action="{{ route('logout') }}" method="POST" class="sidebar-logout-form">
            @csrf
            <button type="submit" class="sidebar-logout-btn" aria-label="Logout" title="Logout">
                <i data-lucide="log-out"></i><span class="logout-label">Logout</span>
            </button>
        </form>
    </div>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
