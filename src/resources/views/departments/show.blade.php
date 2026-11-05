@extends('layouts.app')

@section('title', $department->name . ' - Initiatives')
@section('page-title', $department->name)
@section('page-subtitle', 'Department Initiatives')

@section('content')

<!-- PAGE HEADER -->
<div class="initiatives-header">
    <div class="initiatives-heading">
        <a href="{{ route('departments.index') }}" class="back-link">
            <i data-lucide="arrow-left"></i>
            Back to Departments
        </a>

        <h3>{{ $department->name }}</h3>
        <p>Explore the initiatives registered under this department.</p>
    </div>

    <span class="initiative-count">
        {{ $initiatives->count() }}
        {{ \Illuminate\Support\Str::plural('Initiative', $initiatives->count()) }}
    </span>
</div>

<!-- SEARCH -->
@if($initiatives->isNotEmpty())
<div class="initiative-search">
    <i data-lucide="search"></i>
    <input
        type="search"
        id="initiativeSearch"
        placeholder="Search initiatives..."
        aria-label="Search initiatives"
    >
</div>
@endif

<!-- INITIATIVE CARDS -->
<div class="initiatives-grid" id="initiativesGrid">
    @forelse($initiatives as $initiative)
        {{-- An inactive initiative has no page to open, so its card has no link. --}}
        <a @if($initiative->is_active) href="{{ route('initiatives.show', $initiative->id) }}" @endif @class(['initiative-card', 'is-inactive' => ! $initiative->is_active])>
            <div class="initiative-icon">
                <i data-lucide="clipboard-list"></i>
            </div>

            <div class="initiative-details">
                <h4>{{ $initiative->name }}</h4>
                <span class="initiative-status">
                    <span @class(['status-dot', 'is-inactive' => ! $initiative->is_active])></span>
                    {{ $initiative->is_active ? 'Active' : 'Inactive' }} Initiative
                </span>
            </div>

            @if($initiative->is_active)
            <div class="initiative-arrow">
                <i data-lucide="arrow-up-right"></i>
            </div>
            @endif
        </a>
    @empty
        <div class="initiatives-empty">
            <div class="empty-icon">
                <i data-lucide="folder-open"></i>
            </div>
            <h4>No Initiatives Available</h4>
            <p>
                No active initiatives have been registered
                under this department yet.
            </p>
            <a href="{{ route('departments.index') }}" class="empty-back">
                Back to Departments
            </a>
        </div>
    @endforelse
</div>

<p id="noInitiativeResults" class="no-results" hidden>
    No matching initiatives found.
</p>

@endsection

@push('styles')
<style>
    /* PAGE HEADER */
    .initiatives-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 24px;
    }

    .initiatives-heading { min-width: 0; }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        min-height: 36px;
        margin-bottom: 8px;
        color: #0B6046;
        text-decoration: none;
        font-size: 12px;
        font-weight: 600;
    }

    .back-link svg { width: 16px; height: 16px; }
    .back-link:hover { color: #0B4534; }

    .initiatives-header h3 {
        margin: 0 0 7px;
        color: #0B4534;
        font-size: 21px;
        font-weight: 700;
        line-height: 1.4;
        overflow-wrap: anywhere;
    }

    .initiatives-header p {
        margin: 0;
        color: #687D73;
        font-size: 12px;
        line-height: 1.6;
    }

    .initiative-count {
        flex-shrink: 0;
        padding: 9px 15px;
        border-radius: 20px;
        background: #EAF5EE;
        color: #0B6046;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
    }

    /* SEARCH */
    .initiative-search {
        display: flex;
        align-items: center;
        gap: 10px;
        height: 46px;
        padding: 0 15px;
        margin-bottom: 25px;
        background: #ffffff;
        border: 1px solid #D9E7DE;
        border-radius: 10px;
        transition: border-color .2s, box-shadow .2s;
    }

    .initiative-search:focus-within {
        border-color: #0B6046;
        box-shadow: 0 0 0 3px rgba(11, 96, 70, .08);
    }

    .initiative-search svg {
        width: 18px;
        height: 18px;
        color: #687D73;
        flex-shrink: 0;
    }

    .initiative-search input {
        width: 100%;
        min-width: 0;
        border: none;
        outline: none;
        background: transparent;
        font: inherit;
        font-size: 12px;
        color: #173F32;
    }

    /* INITIATIVES GRID */
    .initiatives-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
    }

    /* INITIATIVE CARD */
    .initiative-card {
        display: flex;
        align-items: center;
        gap: 15px;
        min-height: 118px;
        padding: 20px;
        background: #ffffff;
        border: 1px solid #D9E7DE;
        border-radius: 14px;
        text-decoration: none;
        cursor: pointer;
        transition: border-color .2s, transform .2s, box-shadow .2s;
    }

    .initiative-card:hover {
        border-color: #0B6046;
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(11, 69, 52, .08);
    }

    .initiative-card:focus-visible {
        outline: 2px solid #CAA24A;
        outline-offset: 3px;
    }

    .initiative-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        width: 50px;
        height: 50px;
        background: #28754F;
        border-radius: 12px;
        color: #ffffff;
        transition: background .2s;
    }

    .initiative-icon svg {
        width: 24px;
        height: 24px;
        stroke: currentColor;
    }

    .initiative-card:hover .initiative-icon { background: #0B6046; }

    .initiative-details { flex: 1; min-width: 0; }

    .initiative-details h4 {
        margin: 0 0 9px;
        font-size: 15px;
        font-weight: 600;
        line-height: 1.5;
        color: #173F32;
        overflow-wrap: anywhere;
    }

    /* STATUS */
    .initiative-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 10px;
        font-weight: 500;
        color: #687D73;
    }

    .status-dot {
        width: 7px;
        height: 7px;
        flex-shrink: 0;
        border-radius: 50%;
        background: #35A66B;
    }

    .status-dot.is-inactive { background: #8A9690; }

    .initiative-card.is-inactive { cursor: default; }

    .initiative-card.is-inactive:hover {
        border-color: #D9E7DE;
        transform: none;
        box-shadow: none;
    }

    .initiative-card.is-inactive:hover .initiative-icon { background: #28754F; }

    /* ARROW */
    .initiative-arrow {
        flex-shrink: 0;
        color: #687D73;
        transition: color .2s, transform .2s;
    }

    .initiative-arrow svg { width: 17px; height: 17px; }

    .initiative-card:hover .initiative-arrow {
        color: #0B6046;
        transform: translate(2px, -2px);
    }

    /* EMPTY STATE */
    .initiatives-empty {
        grid-column: 1 / -1;
        padding: 55px 25px;
        text-align: center;
        background: #ffffff;
        border: 1px dashed #D9E7DE;
        border-radius: 14px;
    }

    .empty-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 65px;
        height: 65px;
        margin: 0 auto 18px;
        border-radius: 18px;
        background: #28754F;
        color: #ffffff;
    }

    .empty-icon svg { width: 30px; height: 30px; }

    .initiatives-empty h4 {
        margin: 0 0 9px;
        font-size: 15px;
        color: #0B4534;
    }

    .initiatives-empty p {
        margin: 0 auto 20px;
        max-width: 350px;
        font-size: 12px;
        line-height: 1.7;
        color: #687D73;
    }

    .empty-back {
        display: inline-block;
        padding: 11px 18px;
        background: #0B6046;
        color: #ffffff;
        border-radius: 9px;
        text-decoration: none;
        font-size: 12px;
        font-weight: 600;
        transition: background .2s;
    }

    .empty-back:hover {
        background: #0B4534;
        color: #ffffff;
    }

    .no-results {
        padding: 35px;
        text-align: center;
        color: #687D73;
        font-size: 12px;
    }

    .no-results[hidden] { display: none; }

    /* RESPONSIVE */
    @media (max-width: 1200px) {
        .initiatives-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 650px) {
        .initiatives-grid { grid-template-columns: 1fr; }

        .initiatives-header {
            align-items: flex-start;
            flex-direction: column;
            gap: 14px;
        }

        .initiatives-header h3 { font-size: 19px; }

        .initiative-card {
            min-height: 100px;
            padding: 16px;
            gap: 12px;
        }

        .initiative-icon { width: 44px; height: 44px; }
        .initiative-icon svg { width: 21px; height: 21px; }
        .initiative-details h4 { font-size: 14px; }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('initiativeSearch');
    if (!searchInput) return;

    searchInput.addEventListener('input', function () {
        const search = this.value.toLowerCase().trim();
        const cards = document.querySelectorAll('.initiative-card');
        let visible = 0;

        cards.forEach(card => {
            const name = card.querySelector('h4').textContent.toLowerCase();
            const matches = name.includes(search);
            card.style.display = matches ? '' : 'none';
            if (matches) visible++;
        });

        document.getElementById('noInitiativeResults').hidden =
            visible !== 0 || cards.length === 0;
    });
});
</script>
@endpush
