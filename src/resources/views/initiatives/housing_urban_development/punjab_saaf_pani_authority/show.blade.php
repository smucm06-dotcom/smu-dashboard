@extends('layouts.app')

@section('title', $initiative->name)

{{--
    Punjab Saaf Pani Authority (PSPA). Until its design arrives, the page has the banner, the four uploads
    and the records table, as on the other initiative pages before theirs.
--}}

@section('content')
@php
    // Both are used by the shared upload result box (psrp_shared/import-result).
    $number = static fn ($value) => $value === null ? '—' : number_format((float) $value);
    $importResult = session('psrpImport');
@endphp

<div class="psrp-page" id="psrpPage">

    <nav class="psrp-breadcrumb psrp-wrap" aria-label="Breadcrumb">
        <a href="{{ route('departments.index') }}">Departments</a>
        <i data-lucide="chevron-right" aria-hidden="true"></i>
        <a href="{{ route('departments.show', $initiative->department_id) }}">
            {{ $initiative->department?->name ?? 'Department' }}
        </a>
        <i data-lucide="chevron-right" aria-hidden="true"></i>
        <span aria-current="page">{{ $initiative->name }}</span>
        {{-- The page as it is on the screen, as a PDF download (see psrp_shared/pdf-export) --}}
        <button type="button" class="psrp-pdf-btn" id="pdfExport" data-pdf-skip data-pdf-name="{{ $initiative->name }}">
            <i data-lucide="file-down" aria-hidden="true"></i>
            <span>Export to PDF</span>
        </button>
    </nav>

    <div class="psrp-wrap">
        <section class="psrp-records" id="psrp-records" aria-labelledby="h-records">
            <div class="psrp-findings">
                <div class="sec-h">
                    <h2 id="h-records">Explore the records</h2>
                    <p>Every visit to a water filtration plant. Sort by any column, search by site name, district or tehsil, and click a visit to see everything recorded for it.</p>
                </div>
            </div>

            {{-- Result of the last upload --}}
            @include('initiatives.school_education.psrp_shared.import-result')

            <x-initiative-files
                :initiative="$initiative"
                :files="$files"
                :visits-upload-url="route('initiatives.punjab-saaf-pani-authority.import', $initiative)"
                match-note="Visits are matched by their Date, so nothing is duplicated. Rows that share a Date, such as visits entered together, are all kept."
                list-items="filtration plants"
            />

            <div id="psrpRecordsBody">
                @include('initiatives.school_education.psrp_shared.records')
            </div>
        </section>
    </div>

    {{-- Filled from the clicked row by the script --}}
    @include('initiatives.school_education.psrp_shared.visit-dialog')
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400..800&display=swap">
@include('initiatives.school_education.psrp_shared.findings-styles')
@include('initiatives.school_education.psrp_shared.records-styles')
@php
    // Remarks and photo links can run to hundreds of characters. In the table they are cut short; the
    // visit's window shows them in full, remarks with their line breaks. Columns are found by name.
    $columnKeys = array_keys($table['columns']);
    $longColumns = array_filter([array_search('remarks', $columnKeys, true), array_search('images', $columnKeys, true)], 'is_int');
@endphp
<style>
    @foreach ($longColumns as $index)
    #psrpRecordsBody .psrp-table td:nth-child({{ $index + 1 }}) { max-width: 320px; overflow: hidden; text-overflow: ellipsis; }
    @endforeach
    #psrpVisitDialog .psrp-dialog-fields dd { white-space: pre-line; }
</style>
@endpush

@push('scripts')
@include('initiatives.school_education.psrp_shared.records-script')
@include('initiatives.school_education.psrp_shared.pdf-export')
@endpush
