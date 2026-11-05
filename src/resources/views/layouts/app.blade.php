<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        @yield('title', 'Dashboard') | SMU Dashboard
    </title>

    <!-- Poppins Font -->

    <link rel="preconnect"

          href="https://fonts.googleapis.com">

    <link rel="preconnect"

          href="https://fonts.gstatic.com"

          crossorigin>

    <link

        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"

        rel="stylesheet"

    >

    <!-- Lucide Icons -->

    <script

        src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"

        defer

    ></script>

    <!-- Restore sidebar preference before the page is painted. -->

    <script>

        try {

            if (localStorage.getItem('smuSidebarCollapsed') === '1') {

                document.documentElement.classList.add('sidebar-collapsed');

            }

        } catch (e) {

            // The sidebar still works if browser storage is unavailable.

        }

    </script>

    <style>

        :root {

            --primary: #0B6046;

            --primary-dark: #0B4534;

            --primary-light: #EAF5EE;

            --gold: #CAA24A;

            --sidebar-width: 260px;

            --sidebar-collapsed-width: 68px;

            --topbar-height: 78px;

            --bg: #F5F8F6;

            --white: #ffffff;

            --text: #173F32;

            --muted: #687D73;

            --border: #D9E7DE;

        }

        * {

            box-sizing: border-box;

        }

        html,

        body {

            margin: 0;

            padding: 0;

            min-height: 100%;

        }

        body {

            font-family: 'Poppins', sans-serif;

            background: var(--bg);

            color: var(--text);

            font-size: 13px;

        }

        button,

        input,

        select,

        textarea {

            font: inherit;

        }

        a {

            text-decoration: none;

        }

        button {

            cursor: pointer;

        }

        svg {

            flex-shrink: 0;

        }

        /* APP LAYOUT */

        .app-layout {

            display: flex;

            min-height: 100vh;

        }

        /* SIDEBAR */

        .app-sidebar {

            position: fixed;

            top: 0;

            left: 0;

            bottom: 0;

            z-index: 1000;

            width: var(--sidebar-width);

            display: flex;

            flex-direction: column;

            color: #ffffff;

            border-right: 1px solid rgba(255, 255, 255, 0.12);

            background: var(--primary-dark);

            transition: width 0.25s ease, transform 0.25s ease;

        }

        .sidebar-brand {

            display: flex;

            align-items: center;

            gap: 8px;

            min-height: 98px;

            padding: 20px 13px;

            border-bottom: 1px solid rgba(255,255,255,0.10);

        }

        .sidebar-logo {

            width: 48px;

            height: 48px;

            object-fit: contain;

            background: #ffffff;

            border-radius: 10px;

            padding: 4px;

        }

        .sidebar-brand-text {

            flex: 1 1 auto;

            min-width: 0;

            display: flex;

            flex-direction: column;

            gap: 3px;

        }

        .sidebar-brand-text strong {

            font-size: 14px;

            font-weight: 700;

        }

        .sidebar-brand-text span {

            font-size: 10px;

            color: #B8D6C7;

        }

        /* Sidebar toggle belongs to the brand area (desktop and mobile drawer). */

        .sidebar-brand-toggle {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            width: 34px;

            height: 36px;

            padding: 0;

            margin-left: auto;

            flex: 0 0 34px;

            border-radius: 9px;

            border: 1px solid rgba(255,255,255,.16);

            background: rgba(255,255,255,.07);

            color: #fff;

            transition: background .2s ease, border-color .2s ease;

        }

        .sidebar-brand-toggle:hover {

            background: rgba(255,255,255,.15);

            border-color: rgba(255,255,255,.30);

        }

        .sidebar-brand-toggle:focus-visible {

            outline: 2px solid var(--gold);

            outline-offset: 2px;

        }

        .sidebar-brand-toggle svg { width: 19px; height: 19px; }

        .sidebar-brand-toggle .sidebar-toggle-icon { display: inline-flex; align-items: center; justify-content: center; }

        .sidebar-brand-toggle .sidebar-toggle-icon--collapsed { display: none; }

        @media (min-width: 901px) {

            html.sidebar-collapsed .sidebar-brand-toggle .sidebar-toggle-icon--expanded { display: none; }

            html.sidebar-collapsed .sidebar-brand-toggle .sidebar-toggle-icon--collapsed { display: inline-flex; }

        }

        .sidebar-nav {

            flex: 1;

            overflow-y: auto;

            padding: 24px 12px;

        }

        .nav-link {

            display: flex;

            align-items: center;

            gap: 13px;

            min-height: 46px;

            padding: 12px 14px;

            margin-bottom: 7px;

            border-radius: 10px;

            color: #E0EEE6;

            font-size: 12px;

            font-weight: 500;

            transition: background .2s, color .2s;

        }

        .nav-link:hover {

            background: rgba(255,255,255,0.10);

            color: #ffffff;

        }

        .nav-link.active {

            background: #0B6046;

            color: #ffffff;

            font-weight: 600;

        }

        .nav-icon {

            display: flex;

            align-items: center;

            justify-content: center;

        }

        .nav-icon svg {

            width: 19px;

            height: 19px;

        }

        .sidebar-version {

            display: inline-block;

            margin-top: 13px;

            font-size: 10px;

            color: #A8CEB9;

        }

        /* USER DETAILS IN SIDEBAR FOOTER */

        .sidebar-footer {

            padding: 17px 15px 15px;

            flex-shrink: 0;

        }

        .sidebar-user {

            display: flex;

            align-items: center;

            gap: 10px;

            min-width: 0;

            margin-bottom: 12px;

        }

        .sidebar-user-avatar {

            width: 38px;

            height: 38px;

            flex: 0 0 38px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            background: var(--primary-light);

            color: var(--primary-dark);

            font-size: 14px;

            font-weight: 700;

        }

        .sidebar-user-details {

            display: flex;

            flex: 1;

            min-width: 0;

            flex-direction: column;

            gap: 3px;

        }

        .sidebar-user-details strong,

        .sidebar-user-details span {

            display: block;

            max-width: 100%;

            overflow: hidden;

            white-space: nowrap;

            text-overflow: ellipsis;

        }

        .sidebar-user-details strong {

            color: #ffffff;

            font-size: 11px;

            font-weight: 600;

        }

        .sidebar-user-details span {

            color: #B8D6C7;

            font-size: 10px;

        }

        .sidebar-logout-form { margin: 0 0 15px; }

        .sidebar-logout-btn {

            display: flex;

            align-items: center;

            gap: 10px;

            width: 100%;

            min-height: 42px;

            padding: 10px 12px;

            background: rgba(255, 255, 255, .07);

            color: #ffffff;

            border: 1px solid rgba(255, 255, 255, .18);

            border-radius: 9px;

            text-align: left;

            font-size: 12px;

            font-weight: 600;

            transition: background .2s, border-color .2s;

        }

        .sidebar-logout-btn:hover,

        .sidebar-logout-btn:focus-visible {

            background: var(--primary);

            border-color: var(--gold);

        }

        .sidebar-logout-btn svg { width: 17px; height: 17px; }

        .sidebar-footer-info {

            border-top: 1px solid rgba(255,255,255,.12);

            padding-top: 12px;

        }

        .nav-link.active .nav-icon { color: var(--gold); }

        .sidebar-nav { min-height: 0; }

        @media (max-height: 660px) {

            .sidebar-nav { padding-top: 12px; padding-bottom: 10px; }

            .sidebar-brand { min-height: 80px; padding-top: 12px; padding-bottom: 12px; }

            .sidebar-footer { padding-top: 10px; padding-bottom: 10px; }

        }

        /* DESKTOP ICON-ONLY SIDEBAR (68px wide when collapsed). */

        @media (min-width: 901px) {

            html.sidebar-collapsed .app-sidebar {

                width: var(--sidebar-collapsed-width);

                transform: none;

                pointer-events: auto;

            }

            html.sidebar-collapsed .app-main {

                margin-left: var(--sidebar-collapsed-width);

            }

            html.sidebar-collapsed .app-content {

                max-width: none;

            }

            html.sidebar-collapsed .sidebar-brand {

                min-height: 117px;

                padding: 13px 8px 12px;

                flex-direction: column-reverse;

                justify-content: center;

                gap: 9px;

            }

            html.sidebar-collapsed .sidebar-logo {

                width: 36px;

                height: 36px;

                padding: 3px;

            }

            html.sidebar-collapsed .sidebar-brand-text { display: none; }

            html.sidebar-collapsed .sidebar-brand-toggle {

                width: 40px;

                height: 36px;

                margin: 0;

                flex: 0 0 36px;

            }

            html.sidebar-collapsed .sidebar-nav {

                padding: 18px 8px;

                overflow-x: hidden;

            }

            html.sidebar-collapsed .nav-link {

                justify-content: center;

                padding: 11px 0;

                gap: 0;

            }

            html.sidebar-collapsed .nav-label { display: none; }

            html.sidebar-collapsed .sidebar-footer { padding: 12px 8px; }

            html.sidebar-collapsed .sidebar-user { justify-content: center; }

            html.sidebar-collapsed .sidebar-user-details { display: none; }

            html.sidebar-collapsed .sidebar-logout-btn {

                justify-content: center;

                padding: 10px 0;

                gap: 0;

            }

            html.sidebar-collapsed .sidebar-logout-btn .logout-label { display: none; }

        }

        /* MAIN AREA */

        .app-main {

            min-width: 0;

            flex: 1;

            margin-left: var(--sidebar-width);

            transition: margin-left 0.25s ease;

        }

                /* TOP BAR - SMU MONITORING PORTAL BANNER */

        /* Stays at the top on every page and screen. Anything else a page keeps on
           screen uses top: var(--topbar-height), which is kept up to date below. */

        html {

            scroll-padding-top: calc(var(--topbar-height) + 12px);

        }

        .app-topbar {

            position: sticky;

            top: 0;

            z-index: 100;

            display: grid;

            grid-template-columns: minmax(0, 1fr) clamp(180px, 15vw, 235px);

            grid-template-rows: auto auto;

            column-gap: clamp(16px, 2.4vw, 40px);

            row-gap: 14px;

            align-items: center;

            width: 100%;

            height: auto;

            min-height: 0;

            padding: 20px 30px 18px;

            background: linear-gradient(

                90deg,

                #0B4534 0%,

                #0B4534 40%,

                #0B6046 72%,

                #28754F 100%

            );

            border-bottom: 1px solid rgba(202, 162, 74, 0.25);

            color: #fff;

        }

        .topbar-left {

            grid-column: 1;

            grid-row: 1;

            display: flex;

            align-items: flex-start;

            gap: 12px;

            min-width: 0;

        }

        .topbar-page-title {

            min-width: 0;

            width: 100%;

            max-width: 1400px;

        }

        .topbar-page-title h2 {

            margin: 0 0 8px;

            color: #fff;

            font-size: clamp(25px, 2.6vw, 36px);

            font-weight: 700;

            line-height: 1.2;

            letter-spacing: -.65px;

            overflow-wrap: anywhere;

        }

        .topbar-description {

            max-width: 900px;

            margin: 0;

            color: #EAF5EE;

            font-size: 12px;

            font-weight: 400;

            line-height: 1.7;

        }

        /* Stats occupy their own grid row, so mobile portrait cannot overlap them. */

        .topbar-stats-grid {

            grid-column: 1;

            grid-row: 2;

            display: grid;

            grid-template-columns: repeat(var(--topbar-stat-count, 4), minmax(0, 1fr));

            gap: 10px;

            width: 100%;

            max-width: 1400px;

            min-width: 0;

            margin-top: 0;

        }

        .topbar-stat-card {

            min-width: 0;

            padding: 12px 13px;

            background: rgba(255, 255, 255, .10);

            border: 1px solid rgba(255, 255, 255, .18);

            border-radius: 10px;

        }

        .topbar-stat-label {

            display: block;

            margin-bottom: 5px;

            color: #EAF5EE;

            font-size: 10px;

            line-height: 1.4;

            font-weight: 600;

            letter-spacing: .35px;

            text-transform: uppercase;

            overflow-wrap: anywhere;

        }

        .topbar-stat-value {

            display: block;

            color: #fff;

            font-size: clamp(17px, 1.7vw, 22px);

            line-height: 1.25;

            font-weight: 700;

            overflow-wrap: anywhere;

        }

        /* Chief Minister portrait - aligned right on desktop. */

        .topbar-right {

            grid-column: 2;

            grid-row: 1 / 3;

            display: flex;

            align-items: center;

            justify-content: center;

            flex-direction: column;

            gap: 7px;

            width: 100%;

            min-width: 0;

            text-align: center;

            transform: translateX(-25px);

        }

        .topbar-cm-frame {

            display: flex;

            align-items: center;

            justify-content: center;

            flex: 0 0 auto;

            width: 155px;

            height: 155px;

            padding: 3px;

            border: 4px solid var(--gold);

            border-radius: 50%;

            overflow: hidden;

            background: #EAF5EE;

        }

        .topbar-cm-photo {

            width: 100%;

            height: 100%;

            display: block;

            object-fit: cover;

            object-position: center 18%;

            border-radius: 50%;

        }

        .topbar-cm-fallback {

            display: none;

            width: 100%;

            height: 100%;

            align-items: center;

            justify-content: center;

            color: #0B6046;

            font-size: 30px;

            font-weight: 700;

        }

        .topbar-cm-name {

            margin: 2px 0 0;

            color: #fff;

            font-size: 15px;

            font-weight: 700;

            line-height: 1.35;

            overflow-wrap: anywhere;

        }

        .topbar-cm-role {

            margin: 0;

            color: #EAF5EE;

            font-size: 11px;

            line-height: 1.4;

        }

        /* Only phones/tablets need a topbar menu: desktop toggle lives in the sidebar. */

        .app-topbar .mobile-menu-btn {

            display: none;

            align-items: center;

            justify-content: center;

            flex-shrink: 0;

            padding: 6px;

            border: none;

            background: transparent;

            color: #ffffff;

        }

        .app-topbar .mobile-menu-btn:focus-visible {

            outline: 2px solid var(--gold);

            outline-offset: 2px;

        }

        .app-topbar .mobile-menu-btn svg { width: 23px; height: 23px; }

        @media (min-width: 1920px) {

            .app-topbar {

                padding: 24px clamp(35px, 3vw, 70px);

                min-height: 0;

            }

            .topbar-cm-frame { width: 170px; height: 170px; }

            .topbar-cm-name { font-size: 16px; }

            .topbar-stat-card { padding: 16px 18px; }

        }

        @media (max-width: 1100px) {

            .app-topbar {

                padding: 22px;

                column-gap: 16px;

                row-gap: 16px;

                grid-template-columns: minmax(0, 1fr) 155px;

            }

            .topbar-right { transform: none; }

            .topbar-cm-frame { width: 112px; height: 112px; }

            .topbar-cm-name { font-size: 12px; }

            .topbar-cm-role { font-size: 10px; }

            .topbar-stats-grid { gap: 8px; }

            .topbar-stat-card { padding: 10px; }

        }

        @media (max-width: 900px) {

            .app-topbar {

                padding: 20px 18px;

                min-height: 0;

                grid-template-columns: minmax(0, 1fr) 140px;

            }

            .app-topbar .mobile-menu-btn { display: inline-flex; min-width: 36px; min-height: 36px; }

            .topbar-stats-grid {

                grid-column: 1 / -1;

                grid-row: 2;

            }

            .topbar-right { grid-row: 1; }

        }

        /* The banner never leaves the screen, so on phones it keeps only what fits. */

        @media (max-width: 600px) {

            .app-topbar {

                grid-template-columns: minmax(0, 1fr) 52px;

                column-gap: 8px;

                row-gap: 8px;

                padding: 8px 12px 10px;

            }

            .topbar-left { align-items: center; gap: 6px; }

            .topbar-page-title h2 { margin: 0; font-size: clamp(15px, 4.4vw, 19px); }

            .topbar-description { display: none; }

            .topbar-cm-frame { width: 52px; height: 52px; border-width: 2px; padding: 1px; }

            .topbar-cm-fallback { font-size: 16px; }

            .topbar-cm-name,

            .topbar-cm-role { display: none; }

            /* Extra cards on a future page move to a new row instead of being squeezed. */

            .topbar-stats-grid { grid-template-columns: repeat(auto-fit, minmax(64px, 1fr)); gap: 6px; }

            .topbar-stat-card { padding: 6px; }

            .topbar-stat-label { margin-bottom: 2px; font-size: 8px; letter-spacing: 0; }

            .topbar-stat-value { font-size: 15px; }

        }

        @media (max-width: 360px) {

            .topbar-left { gap: 5px; }

            .app-topbar .mobile-menu-btn { padding: 3px; }

            .topbar-stats-grid { gap: 4px; }

            .topbar-stat-card { padding: 6px 4px; }

        }

        @media (prefers-reduced-motion: reduce) {

            .app-sidebar, .app-main {

                transition: none;

            }

        }

        /* The same on short screens: laptops, large zoom and phones held sideways. */

        @media (min-width: 601px) and (max-height: 860px) {

            .app-topbar {

                grid-template-columns: minmax(0, 1fr) auto;

                column-gap: 16px;

                row-gap: 8px;

                padding: 10px 20px;

            }

            .topbar-page-title h2 { margin: 0; font-size: clamp(17px, 1.9vw, 24px); }

            .topbar-description { display: none; }

            .topbar-stats-grid { grid-column: 1; }

            .topbar-right {

                grid-row: 1 / 3;

                display: grid;

                grid-template-columns: auto auto;

                column-gap: 10px;

                text-align: left;

                transform: none;

            }

            .topbar-cm-frame { grid-row: 1 / 3; width: 72px; height: 72px; border-width: 3px; padding: 2px; }

            .topbar-cm-name { align-self: end; margin: 0; font-size: 13px; }

            .topbar-cm-role { align-self: start; font-size: 11px; }

            .topbar-stat-card { padding: 6px 10px; }

            .topbar-stat-label { margin-bottom: 2px; }

            .topbar-stat-value { font-size: 16px; }

        }

        @media (min-width: 601px) and (max-height: 480px) {

            .topbar-cm-name,

            .topbar-cm-role { display: none; }

        }

        /* iPhones zoom the whole page when a focused field's text is under 16px. */

        @media (max-width: 768px) {

            input,

            select,

            textarea {

                font-size: 16px !important;

            }

        }

        /* CONTENT */

        .app-content {

            padding: 30px;

            max-width: 1700px;

            margin: 0 auto;

        }

        .content-card {

            background: #ffffff;

            border: 1px solid var(--border);

            border-radius: 15px;

            padding: 25px;

        }

        /* Prevent background scrolling while the mobile navigation is open. */

        body.sidebar-drawer-open {

            overflow: hidden;

        }

        /* MOBILE SIDEBAR */

        .mobile-menu-btn {

            display: none;

            border: none;

            background: transparent;

            color: var(--primary-dark);

            padding: 5px;

        }

        .mobile-menu-btn svg {

            width: 23px;

            height: 23px;

        }

        .sidebar-overlay {

            display: none;

        }

        @media (max-width: 900px) {

            .app-sidebar {

                transform: translateX(-100%);

            }

            .app-sidebar.open {

                transform: translateX(0);

            }

            .app-main {

                margin-left: 0;

            }

            .mobile-menu-btn {

                display: flex;

            }

            .sidebar-overlay.open {

                display: block;

                position: fixed;

                inset: 0;

                z-index: 999;

                background: rgba(0,0,0,.40);

            }

            .app-content {

                padding: 20px 15px;

            }

        }

    </style>

    @stack('styles')

</head>

<body>

    <div class="app-layout">

        <!-- REUSABLE SIDEBAR -->

        <x-sidebar />

        <div class="app-main">

            <!-- REUSABLE TOP BAR -->

            <x-topbar :topbar="$topbar ?? []" />

            <!-- PAGE CONTENT -->

            <main class="app-content">

                @yield('content')

            </main>

        </div>

    </div>

    <script>

        // The banner's height changes with the screen and the length of the page title.

        const appTopbar = document.querySelector('.app-topbar');

        function updateTopbarHeight() {

            document.documentElement.style.setProperty('--topbar-height', appTopbar.getBoundingClientRect().height + 'px');

        }

        updateTopbarHeight();

        new ResizeObserver(updateTopbarHeight).observe(appTopbar);

        const sidebarMediaQuery = window.matchMedia('(max-width: 900px)');

        function updateSidebarToggleButton() {

            const sidebar = document.getElementById('appSidebar');

            const brandToggle = document.getElementById('sidebarToggleButton');

            const mobileToggle = document.getElementById('mobileSidebarToggleButton');

            if (!sidebar) return;

            const isMobile = sidebarMediaQuery.matches;

            const mobileOpen = sidebar.classList.contains('open');

            const desktopExpanded = !document.documentElement.classList.contains('sidebar-collapsed');

            if (brandToggle) {

                brandToggle.setAttribute('aria-expanded', String(isMobile ? mobileOpen : desktopExpanded));

                brandToggle.setAttribute('aria-label', isMobile ? 'Close navigation' : (desktopExpanded ? 'Collapse sidebar' : 'Expand sidebar'));

                brandToggle.title = isMobile ? 'Close sidebar' : (desktopExpanded ? 'Collapse sidebar' : 'Expand sidebar');

            }

            if (mobileToggle) {

                mobileToggle.setAttribute('aria-expanded', String(mobileOpen));

                mobileToggle.setAttribute('aria-label', mobileOpen ? 'Close navigation' : 'Open navigation');

                mobileToggle.title = mobileOpen ? 'Close sidebar' : 'Open sidebar';

            }

        }

        function toggleSidebar() {

            const sidebar = document.getElementById('appSidebar');

            const overlay = document.getElementById('sidebarOverlay');

            if (!sidebar || !overlay) return;

            if (sidebarMediaQuery.matches) {

                // Tablet/phone: use the original overlay drawer.

                const open = !sidebar.classList.contains('open');

                sidebar.classList.toggle('open', open);

                overlay.classList.toggle('open', open);

                document.body.classList.toggle('sidebar-drawer-open', open);

            } else {

                // Desktop: shrink the sidebar to a permanent 68px icon rail.

                const collapsed = document.documentElement.classList.toggle('sidebar-collapsed');

                try {

                    localStorage.setItem('smuSidebarCollapsed', collapsed ? '1' : '0');

                } catch (e) {

                    // Still works for the current page if storage is unavailable.

                }

                sidebar.classList.remove('open');

                overlay.classList.remove('open');

                document.body.classList.remove('sidebar-drawer-open');

            }

            updateSidebarToggleButton();

        }

        function closeSidebar() {

            const sidebar = document.getElementById('appSidebar');

            const overlay = document.getElementById('sidebarOverlay');

            if (sidebar) sidebar.classList.remove('open');

            if (overlay) overlay.classList.remove('open');

            document.body.classList.remove('sidebar-drawer-open');

            updateSidebarToggleButton();

        }

        document.addEventListener('DOMContentLoaded', function () {

            if (window.lucide) {

                lucide.createIcons();

            }

            updateSidebarToggleButton();

            document.addEventListener('keydown', function(event) {

                if (event.key === 'Escape') closeSidebar();

            });

        });

        // Close any open mobile overlay when changing screen sizes.

        if (sidebarMediaQuery.addEventListener) {

            sidebarMediaQuery.addEventListener('change', closeSidebar);

        } else if (sidebarMediaQuery.addListener) {

            sidebarMediaQuery.addListener(closeSidebar);

        }

    </script>

    @stack('scripts')

</body>

</html>
