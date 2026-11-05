@extends('layouts.app')

@section('title', $initiative->name)
@section('page-title', $initiative->name)
@section('page-subtitle', 'Initiative Records')

@section('content')
@php
    // These labels improve readability without restricting future tables.
    $columnLabels = [
        'source_sr' => 'Sr.',
        'visited_at' => 'Visit Date / Time',
        'district' => 'District',
        'emis_code' => 'EMIS Code',
        'school_name' => 'School Name',
        'staff_salary_status' => 'Staff Salary Status',
        'pts_council_established' => 'PTS Council Established',
        'council_meetings_count' => 'Council Meetings Count',
        'students_charged_fees' => 'Students Charged Fees',
        'students_enrolled' => 'Students Enrolled',
        'students_at_handover' => 'Students at Handover',
        'students_present' => 'Students Present on Visit Day',
        'total_teachers' => 'Total Teachers',
        'teachers_present' => 'Teachers Present at Visit',
        'total_classrooms' => 'Total Classrooms',
        'total_washrooms' => 'Total Washrooms',
        'dangerous_classrooms' => 'Dangerous Classrooms',
        'handover_status' => 'School Handover Status',
        'boundary_wall_available' => 'Boundary Wall Available',
        'classroom_condition' => 'Classroom Condition',
        'washroom_cleanliness' => 'Washroom Condition / Cleanliness',
        'school_cleanliness' => 'Overall School Cleanliness',
        'clean_drinking_water_available' => 'Clean Drinking Water Available',
        'free_textbooks_available' => 'Free Textbooks Available',
        'one_grade_per_classroom' => 'One Grade per Classroom',
        'furniture_available' => 'Furniture Available',
        'first_aid_available' => 'First Aid Available',
        'teachers_enrolled_pef_training' => 'Teachers Enrolled in PEF Training',
        'teachers_nominated_pef_cpdp' => 'Teachers Nominated for PEF CPDP',
        'total_desks' => 'Total Desks',
        'total_room_chairs' => 'Total Room Chairs',
        'total_air_fans' => 'Total Air Fans',
        'latitude' => 'Latitude',
        'longitude' => 'Longitude',
    ];

    // Explicit boolean fields: do not convert ordinary numeric 0/1 values.
    $booleanFields = [
        'staff_salary_status',
        'pts_council_established',
        'students_charged_fees',
        'handover_status',
        'boundary_wall_available',
        'clean_drinking_water_available',
        'free_textbooks_available',
        'one_grade_per_classroom',
        'furniture_available',
        'first_aid_available',
    ];

    $displayColumns = array_values(array_diff(
        $columns,
        ['id', 'created_at', 'updated_at', 'last_import_id']
    ));

    $labelFor = static function ($column) use ($columnLabels) {
        return $columnLabels[$column]
            ?? ucwords(str_replace('_', ' ', $column));
    };

    $formatValue = static function ($column, $value) use ($booleanFields) {
        if ($value === null || $value === '') {
            return '—';
        }

        if (in_array($column, $booleanFields, true)) {
            if (in_array($value, [true, 1, '1', 'Yes', 'yes'], true)) {
                return 'Yes';
            }

            if (in_array($value, [false, 0, '0', 'No', 'no'], true)) {
                return 'No';
            }
        }

        // Preserve identifiers, coordinates, dates and numeric precision.
        return (string) $value;
    };

    // Only show search when supported by the controller.
    $searchEnabled = isset($search);
    $searchText = $searchEnabled ? (string) $search : '';

    $currentPage = $records->currentPage();
    $lastPage = $records->lastPage();
    $startPage = max(1, $currentPage - 2);
    $endPage = min($lastPage, $currentPage + 2);
@endphp

<div class="initiative-page">

    {{-- Breadcrumb --}}
    <nav class="page-navigation" aria-label="Breadcrumb">
        <a href="{{ route('departments.index') }}">
            Departments
        </a>

        <i data-lucide="chevron-right" aria-hidden="true"></i>

        <a href="{{ route('departments.show', $initiative->department_id) }}">
            {{ $initiative->department?->name ?? 'Department' }}
        </a>

        <i data-lucide="chevron-right" aria-hidden="true"></i>

        <span aria-current="page">{{ $initiative->name }}</span>
    </nav>

    {{-- Heading --}}
    <div class="data-heading">
        <div>
            <h3>{{ $initiative->name }}</h3>
            <p>Browse records imported for this initiative.</p>
        </div>

        <span class="data-count">
            {{ number_format($records->total()) }}
            {{ $searchText !== '' ? 'Matching Records' : 'Records' }}
        </span>
    </div>

    {{-- Records --}}
    <div class="records-panel">

        <div class="records-header">
            <h4>Initiative Records</h4>
            <p>
                {{ count($displayColumns) }} data fields
                &middot; {{ $records->perPage() }} records per page
            </p>
        </div>

        @if ($errors->any())
            <div class="records-errors" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{-- Server-side search, when provided by the controller --}}
        @if ($searchEnabled)
            <form
                method="GET"
                action="{{ route('initiatives.show', $initiative->id) }}"
                class="records-search-form"
            >
                <div class="records-search">
                    <i data-lucide="search" aria-hidden="true"></i>

                    <input
                        type="search"
                        name="search"
                        value="{{ $searchText }}"
                        placeholder="Search records..."
                        aria-label="Search initiative records"
                        maxlength="200"
                    >
                </div>

                <button type="submit" class="search-btn">
                    Search
                </button>

                @if ($searchText !== '')
                    <a
                        class="clear-btn"
                        href="{{ route('initiatives.show', $initiative->id) }}"
                    >
                        Clear
                    </a>
                @endif
            </form>
        @endif

        {{-- Desktop table --}}
        <div
            class="records-table-wrapper"
            role="region"
            aria-label="Initiative records table"
            tabindex="0"
        >
            <table class="records-table">
                <thead>
                    <tr>
                        @foreach ($displayColumns as $column)
                            <th scope="col">{{ $labelFor($column) }}</th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            @foreach ($displayColumns as $column)
                                <td @class([
                                    'record-name' => in_array(
                                        $column,
                                        ['school_name', 'site', 'name'],
                                        true
                                    ),
                                ])>
                                    {{ $formatValue(
                                        $column,
                                        $record->{$column} ?? null
                                    ) }}
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="{{ max(1, count($displayColumns)) }}"
                                class="table-empty"
                            >
                                {{ $searchText !== ''
                                    ? 'No matching records found.'
                                    : 'No records available for this initiative yet.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile cards: show the same fields as the desktop table --}}
        <div class="mobile-records">
            @forelse ($records as $record)
                <article class="mobile-record-card">
                    <div class="mobile-record-top">
                        <h4>
                            {{ $record->school_name
                                ?? $record->site
                                ?? $record->name
                                ?? 'Record' }}
                        </h4>

                        <span>
                            #{{ $record->source_sr ?? $record->id }}
                        </span>
                    </div>

                    <div class="mobile-record-details">
                        @foreach ($displayColumns as $column)
                            <div>
                                <span>{{ $labelFor($column) }}</span>
                                <strong>
                                    {{ $formatValue(
                                        $column,
                                        $record->{$column} ?? null
                                    ) }}
                                </strong>
                            </div>
                        @endforeach
                    </div>
                </article>
            @empty
                <p class="table-empty">
                    {{ $searchText !== ''
                        ? 'No matching records found.'
                        : 'No records available for this initiative yet.' }}
                </p>
            @endforelse
        </div>

        {{-- Pagination: no Bootstrap or Tailwind required --}}
        @if ($records->hasPages())
            <nav class="custom-pagination" aria-label="Records pagination">
                @if ($records->onFirstPage())
                    <span
                        class="pagination-button is-disabled"
                        aria-disabled="true"
                        aria-label="Previous page"
                    >&lsaquo;</span>
                @else
                    <a
                        class="pagination-button"
                        href="{{ $records->previousPageUrl() }}"
                        rel="prev"
                        aria-label="Previous page"
                    >&lsaquo;</a>
                @endif

                @if ($startPage > 1)
                    <a class="pagination-button"
                       href="{{ $records->url(1) }}"
                       aria-label="Page 1">1</a>

                    @if ($startPage > 2)
                        <span class="pagination-ellipsis" aria-hidden="true">
                            &hellip;
                        </span>
                    @endif
                @endif

                @for ($page = $startPage; $page <= $endPage; $page++)
                    @if ($page === $currentPage)
                        <span
                            class="pagination-button is-active"
                            aria-current="page"
                        >{{ $page }}</span>
                    @else
                        <a
                            class="pagination-button"
                            href="{{ $records->url($page) }}"
                            aria-label="Page {{ $page }}"
                        >{{ $page }}</a>
                    @endif
                @endfor

                @if ($endPage < $lastPage)
                    @if ($endPage < $lastPage - 1)
                        <span class="pagination-ellipsis" aria-hidden="true">
                            &hellip;
                        </span>
                    @endif

                    <a
                        class="pagination-button"
                        href="{{ $records->url($lastPage) }}"
                        aria-label="Page {{ $lastPage }}"
                    >{{ $lastPage }}</a>
                @endif

                @if ($records->hasMorePages())
                    <a
                        class="pagination-button"
                        href="{{ $records->nextPageUrl() }}"
                        rel="next"
                        aria-label="Next page"
                    >&rsaquo;</a>
                @else
                    <span
                        class="pagination-button is-disabled"
                        aria-disabled="true"
                        aria-label="Next page"
                    >&rsaquo;</span>
                @endif
            </nav>
        @endif

        <p class="records-footer">
            Showing {{ $records->firstItem() ?? 0 }}
            to {{ $records->lastItem() ?? 0 }}
            of {{ number_format($records->total()) }} records
        </p>
    </div>
</div>
@endsection

@push('styles')
<style>
    .initiative-page {
        width: 100%;
        min-width: 0;
        color: var(--text, #173F32);
    }

    .initiative-page .page-navigation {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 22px;
        font-size: 11px;
        color: var(--muted, #687D73);
        overflow-wrap: anywhere;
    }

    .initiative-page .page-navigation a {
        color: var(--primary, #0B6046);
        font-weight: 600;
        text-decoration: none;
    }

    .initiative-page .page-navigation a:hover {
        text-decoration: underline;
    }

    .initiative-page .page-navigation svg {
        width: 13px;
        height: 13px;
        flex-shrink: 0;
    }

    .initiative-page .data-heading {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        margin-bottom: 25px;
    }

    .initiative-page .data-heading > div {
        min-width: 0;
    }

    .initiative-page .data-heading h3 {
        margin: 0 0 6px;
        color: var(--text, #173F32);
        font-size: 21px;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .initiative-page .data-heading p,
    .initiative-page .records-header p {
        margin: 0;
        font-size: 12px;
        color: var(--muted, #687D73);
        line-height: 1.6;
    }

    .initiative-page .data-count {
        padding: 9px 15px;
        background: var(--primary-light, #EAF5EE);
        color: var(--primary, #0B6046);
        border-radius: 30px;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
    }

    .initiative-page .records-panel {
        background: #fff;
        border: 1px solid var(--border, #D9E7DE);
        border-radius: 14px;
        padding: 22px;
        min-width: 0;
    }

    .initiative-page .records-header {
        margin-bottom: 20px;
    }

    .initiative-page .records-header h4 {
        margin: 0 0 6px;
        color: var(--text, #173F32);
        font-size: 16px;
        font-weight: 700;
    }

    .initiative-page .records-errors {
        border: 1px solid #E8BBBB;
        border-radius: 9px;
        padding: 10px 14px;
        margin-bottom: 16px;
        background: #FFF5F5;
        color: #8A2222;
    }

    .initiative-page .records-errors p {
        margin: 4px 0;
    }

    .initiative-page .records-search-form {
        display: flex;
        gap: 10px;
        margin-bottom: 22px;
    }

    .initiative-page .records-search {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1;
        min-width: 0;
        padding: 0 13px;
        height: 44px;
        border: 1px solid var(--border, #D9E7DE);
        border-radius: 9px;
    }

    .initiative-page .records-search:focus-within {
        border-color: var(--primary, #0B6046);
    }

    .initiative-page .records-search svg {
        width: 17px;
        height: 17px;
        color: var(--muted, #687D73);
        flex-shrink: 0;
    }

    .initiative-page .records-search input {
        border: none;
        outline: none;
        background: transparent;
        width: 100%;
        min-width: 0;
        font: inherit;
        font-size: 12px;
        color: var(--text, #173F32);
    }

    .initiative-page .search-btn,
    .initiative-page .clear-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 20px;
        height: 44px;
        border-radius: 9px;
        font: inherit;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
    }

    .initiative-page .search-btn {
        border: none;
        background: var(--primary, #0B6046);
        color: #fff;
    }

    .initiative-page .search-btn:hover {
        background: var(--primary-dark, #0B4534);
    }

    .initiative-page .clear-btn {
        border: 1px solid var(--border, #D9E7DE);
        background: #fff;
        color: var(--primary, #0B6046);
    }

    .initiative-page .records-table-wrapper {
        overflow-x: auto;
        width: 100%;
        max-width: 100%;
        border: 1px solid var(--border, #D9E7DE);
        border-radius: 10px;
    }

    .initiative-page .records-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
        text-align: left;
    }

    .initiative-page .records-table th {
        padding: 15px 13px;
        background: var(--primary-light, #EAF5EE);
        color: var(--text, #173F32);
        font-weight: 700;
        white-space: nowrap;
    }

    .initiative-page .records-table td {
        padding: 14px 13px;
        border-top: 1px solid var(--border, #D9E7DE);
        color: var(--text, #173F32);
        vertical-align: top;
        min-width: 110px;
        overflow-wrap: anywhere;
    }

    .initiative-page .records-table tbody tr:hover {
        background: #F5F8F6;
    }

    .initiative-page .records-table .record-name {
        min-width: 230px;
        font-weight: 600;
        line-height: 1.6;
    }

    .initiative-page .table-empty {
        text-align: center;
        padding: 30px !important;
        font-size: 12px;
        color: var(--muted, #687D73);
    }

    .initiative-page .custom-pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        flex-wrap: wrap;
        gap: 7px;
        margin-top: 24px;
        padding: 4px 0;
    }

    .initiative-page .pagination-button {
        display: inline-flex;
        justify-content: center;
        align-items: center;
        min-width: 36px;
        height: 36px;
        padding: 0 10px;
        border: 1px solid var(--border, #D9E7DE);
        border-radius: 9px;
        background: #fff;
        color: var(--primary, #0B6046);
        font-size: 12px;
        font-weight: 600;
        line-height: 1;
        text-decoration: none;
        transition: background .2s, border-color .2s;
    }

    .initiative-page a.pagination-button:hover {
        border-color: var(--primary, #0B6046);
        background: var(--primary-light, #EAF5EE);
    }

    .initiative-page a:focus-visible,
    .initiative-page button:focus-visible,
    .initiative-page .records-table-wrapper:focus-visible {
        outline: 2px solid var(--gold, #CAA24A);
        outline-offset: 3px;
    }

    .initiative-page .pagination-button.is-active {
        border-color: var(--primary, #0B6046);
        background: var(--primary, #0B6046);
        color: #fff;
    }

    .initiative-page .pagination-button.is-disabled {
        background: #F5F8F6;
        color: #84958C;
        cursor: default;
    }

    .initiative-page .pagination-ellipsis {
        padding: 0 3px;
        color: var(--muted, #687D73);
    }

    .initiative-page .records-footer {
        color: var(--muted, #687D73);
        font-size: 11px;
        margin: 17px 0 0;
    }

    .initiative-page .mobile-records {
        display: none;
    }

    .initiative-page .mobile-record-card {
        border: 1px solid var(--border, #D9E7DE);
        border-radius: 12px;
        padding: 17px;
        margin-bottom: 12px;
        background: #fff;
    }

    .initiative-page .mobile-record-top {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 15px;
    }

    .initiative-page .mobile-record-top h4 {
        margin: 0;
        font-size: 12px;
        line-height: 1.6;
        color: var(--text, #173F32);
        overflow-wrap: anywhere;
        min-width: 0;
    }

    .initiative-page .mobile-record-top > span {
        font-size: 11px;
        color: var(--muted, #687D73);
        white-space: nowrap;
    }

    .initiative-page .mobile-record-details {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 15px;
    }

    .initiative-page .mobile-record-details div {
        min-width: 0;
    }

    .initiative-page .mobile-record-details span {
        display: block;
        margin-bottom: 5px;
        font-size: 10px;
        line-height: 1.5;
        color: var(--muted, #687D73);
        overflow-wrap: anywhere;
    }

    .initiative-page .mobile-record-details strong {
        display: block;
        font-size: 11px;
        color: var(--text, #173F32);
        overflow-wrap: anywhere;
    }

    @media (max-width: 768px) {
        .initiative-page .records-table-wrapper {
            display: none;
        }

        .initiative-page .mobile-records {
            display: block;
        }

        .initiative-page .records-panel {
            padding: 16px;
        }

        .initiative-page .data-heading {
            align-items: flex-start;
            flex-direction: column;
        }

        .initiative-page .records-search-form {
            flex-wrap: wrap;
        }

        .initiative-page .records-search {
            flex-basis: 100%;
        }
    }

    @media (max-width: 450px) {
        .initiative-page .custom-pagination {
            gap: 5px;
        }

        .initiative-page .pagination-button {
            min-width: 32px;
            height: 32px;
            padding: 0 8px;
            font-size: 11px;
        }

        .initiative-page .mobile-record-details {
            gap: 12px;
        }

        .initiative-page .data-heading h3 {
            font-size: 18px;
        }
    }
</style>
@endpush
