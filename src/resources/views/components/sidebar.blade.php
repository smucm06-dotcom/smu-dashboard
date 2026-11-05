<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-brand">
        <img src="{{ asset('images/logo.png') }}" alt="SMU Logo" class="sidebar-logo">
        <div class="sidebar-brand-text">
            <strong>SMU Dashboard</strong>
            <span>Monitoring System</span>
        </div>
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

    <nav class="sidebar-nav" aria-label="Main navigation">
        <a href="{{ route('departments.index') }}"
           title="Departments"
           class="nav-link {{ request()->routeIs('departments.*', 'initiatives.*') ? 'active' : '' }}"
           >
            <span class="nav-icon"><i data-lucide="building-2"></i></span>
            <span class="nav-label">Departments</span>
        </a>

        <!-- Reports route is not registered yet; keep the placeholder non-interactive. -->
        <div class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" title="Reports (coming soon)">
            <span class="nav-icon"><i data-lucide="chart-column-increasing"></i></span>
            <span class="nav-label">Reports</span>
        </div>
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
