@props(['topbar' => []])

@php
    // Display only: each page's controller sends its own "topbar" data.
    $title = $topbar['title'] ?? 'SMU Monitoring Portal';
    $description = $topbar['description'] ?? 'Monitor government initiatives, track departmental performance, and review field observations to strengthen public service delivery across Punjab.';
    $stats = $topbar['stats'] ?? [];
@endphp

<header class="app-topbar">

    <div class="topbar-left">

        <!-- Mobile navigation button only -->
        <button
            type="button"
            class="mobile-menu-btn"
            id="mobileSidebarToggleButton"
            onclick="toggleSidebar()"
            aria-label="Open navigation"
            aria-controls="appSidebar"
            aria-expanded="false"
        >
            <i data-lucide="menu"></i>
        </button>

        <div class="topbar-page-title">
            <h2>{{ $title }}</h2>

            <p class="topbar-description">
                {{ $description }}
            </p>
        </div>

    </div>

    <!-- DYNAMIC STATISTICS -->
    @if ($stats !== [])
        <div class="topbar-stats-grid" style="--topbar-stat-count: {{ count($stats) }}" aria-label="Page statistics">

            @foreach ($stats as $stat)
                <div class="topbar-stat-card">

                    <span class="topbar-stat-label">
                        {{ $stat['label'] }}
                    </span>

                    <strong class="topbar-stat-value">
                        {{ ($stat['value'] ?? null) === null ? '—' : number_format((float) $stat['value']) }}
                    </strong>

                </div>
            @endforeach

        </div>
    @endif

    <!-- CHIEF MINISTER PORTRAIT -->
    <div class="topbar-right">

        <div class="topbar-cm-frame">

            <img
                src="{{ asset('images/cm-topbar.jpeg') }}"
                alt="Chief Minister Punjab"
                class="topbar-cm-photo"
                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
            >

            <span class="topbar-cm-fallback" aria-hidden="true">
                CM
            </span>

        </div>

        <p class="topbar-cm-name">
            Maryam Nawaz Sharif
        </p>

        <p class="topbar-cm-role">
            Chief Minister, Punjab
        </p>

    </div>

</header>
