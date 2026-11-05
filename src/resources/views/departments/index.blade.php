
@extends('layouts.app')

@section('title', 'Departments')
@section('page-title', 'Departments')
@section('page-subtitle', 'Government of Punjab Departments')

@section('content')

<div class="department-search">
    <i data-lucide="search"></i>
    <input
        type="search"
        id="departmentSearch"
        placeholder="Search departments..."
        aria-label="Search departments"
    >
</div>

<div class="departments-grid" id="departmentsGrid">

    @forelse($departments as $department)

        @php
            $icons = [
                'AGR' => 'sprout',
                'AGR' => 'car-front',
                'FWF' => 'trees',
                'CW' => 'construction',
                'ENE' => 'zap',
                'ECC' => 'leaf',
                'FIN' => 'wallet',
                'HEP' => 'heart-pulse',
                'HED' => 'graduation-cap',
                'HOM' => 'shield',
                'HUDPHE' => 'building-2',
                'HUDPH' => 'building-2',
                'HRMA' => 'hand-heart',
                'IND' => 'factory',
                'INC' => 'radio',
                'LGCD' => 'landmark',
                'LDD' => 'beef',
                'PND' => 'chart-no-axes-combined',
                'POL' => 'shield-check',
                'SED' => 'school',
                'SGA' => 'briefcase-business',
                'SDE' => 'wrench',
                'SW' => 'heart-handshake',
                'SPE' => 'accessibility',
                'SHCME' => 'hospital',
                'SYA' => 'trophy',
                'TAM' => 'map',
                'TMT' => 'bus',
                'WDD' => 'users',
                'PSCA' => 'cctv',
                'PITB' => 'monitor-cog',
                'IC' => 'clipboard-check',
                'LHR' => 'hard-hat',
                'PCCM' => 'scale',
            ];

            $icon = $icons[$department->code] ?? 'building-2';
        @endphp

        <a class="department-card" href="{{ route('departments.show', $department->id) }}">

            <div class="department-icon">
                <i data-lucide="{{ $icon }}"></i>
            </div>

            <div class="department-details">
                <h4>{{ $department->name }}</h4>
            </div>

            <i
                data-lucide="arrow-up-right"
                class="department-arrow"
            ></i>

        </a>

    @empty

        <div class="empty-state">
            No departments available.
        </div>

    @endforelse

</div>

<p id="noResults" class="empty-state" hidden>
    No matching departments found.
</p>

@endsection


@push('styles')
<style>
    .department-count {
        padding: 8px 14px;
        border-radius: 20px;
        background: #EAF5EE;
        color: #0B6046;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
    }

    /* SEARCH */

    .department-search {
        display: flex;
        align-items: center;
        gap: 10px;
        height: 46px;
        padding: 0 15px;
        margin-bottom: 24px;
        background: #ffffff;
        border: 1px solid #D9E7DE;
        border-radius: 10px;
        transition: border-color .2s, box-shadow .2s;
    }

    .department-search:focus-within {
        border-color: #0B6046;
        box-shadow: 0 0 0 3px rgba(11, 96, 70, .08);
    }

    .department-search svg {
        width: 18px;
        height: 18px;
        color: #687D73;
        flex-shrink: 0;
    }

    .department-search input {
        width: 100%;
        min-width: 0;
        border: none;
        outline: none;
        background: transparent;
        font: inherit;
        font-size: 12px;
        color: #173F32;
    }

    /* DEPARTMENT CARDS */

    .departments-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
    }

    .department-card {
        display: flex;
        align-items: center;
        gap: 15px;
        min-height: 112px;
        padding: 20px;
        background: #ffffff;
        border: 1px solid #D9E7DE;
        border-radius: 14px;
        text-decoration: none;
        cursor: pointer;
        transition:
            border-color .2s,
            transform .2s,
            box-shadow .2s;
    }

    .department-card:hover {
        border-color: #0B6046;
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(11, 69, 52, .08);
    }

    .department-card:focus-visible {
        outline: 2px solid #CAA24A;
        outline-offset: 3px;
    }

    /* GREEN ICON BOX */

    .department-icon {
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

    .department-icon svg {
        width: 24px;
        height: 24px;
        color: #ffffff;
        stroke: currentColor;
    }

    .department-card:hover .department-icon {
        background: #0B4534;
    }

    /* DEPARTMENT NAME */

    .department-details {
        flex: 1;
        min-width: 0;
    }

    .department-details h4 {
        margin: 0;
        font-size: 15px;
        font-weight: 600;
        line-height: 1.5;
        color: #173F32;
        overflow-wrap: anywhere;
    }

    .department-details span {
        font-size: 10px;
        color: #687D73;
    }

    /* RIGHT ARROW */

    .department-arrow {
        width: 17px;
        height: 17px;
        color: #687D73;
        flex-shrink: 0;
        transition: color .2s, transform .2s;
    }

    .department-card:hover .department-arrow {
        color: #0B6046;
        transform: translate(2px, -2px);
    }

    /* EMPTY STATES */

    .empty-state {
        grid-column: 1 / -1;
        padding: 35px;
        text-align: center;
        color: #687D73;
        font-size: 12px;
    }

    /* TABLET */

    @media (max-width: 1200px) {
        .departments-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    /* NARROW SCREENS WITH THE SIDEBAR OPEN */

    @media (min-width: 901px) and (max-width: 1000px) {
        .department-card {
            gap: 12px;
            padding: 16px;
        }
    }

    /* MOBILE */

    @media (max-width: 650px) {
        .departments-grid {
            grid-template-columns: 1fr;
        }

        .department-card {
            min-height: 95px;
            padding: 16px;
        }

        .department-details h4 {
            font-size: 14px;
        }
    }
</style>
@endpush


@push('scripts')
<script>
    document.getElementById('departmentSearch')
        .addEventListener('input', function () {

            const search = this.value.toLowerCase().trim();
            const cards = document.querySelectorAll('.department-card');
            let visible = 0;

            cards.forEach(card => {
                const name = card.querySelector('h4').textContent.toLowerCase();
                const matches = name.includes(search);

                card.style.display = matches ? '' : 'none';

                if (matches) visible++;
            });

            document.getElementById('noResults').hidden =
                visible !== 0 || cards.length === 0;
        });
</script>
@endpush
